<?php

namespace App\Helpers;

use App\Classes\Process;
use App\Enum\GitHubEnum;
use App\Enum\TagEnum;
use App\Enum\ValidationTypeEnum;
use App\MyOps;
use App\Traits\ConsoleUITrait;
use DateTime;
use Exception;

/**
 * this is an AWS Helper
 */
class AWSHelper
{
    use ConsoleUITrait;

    const ELB_TEMP_DIR = "tmp/elb-version";
    const ELB_EBEXTENSIONS_DIR = ".ebextensions"; // should place at inside elb version dir
    const ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME = "blockdevice-xvdcz.config";
    const ELB_DOCKERRUN_FILE_NAME = "Dockerrun.aws.json";
    const ELB_LOG_UPDATE_SUCCESSFULLY = "Environment update completed successfully.";
    const ELB_LOG_UPDATE_FAILED = "Failed to deploy application.";

    const AWS_CONFIGURATION_DIR = '.aws';
    const AWS_CONFIGURATION_CREDENTIALS_FILE = 'credentials';
    const AWS_CONFIGURATION_CREDENTIALS_FILE_PROD = 'credentials_production';
    const AWS_CONFIGURATION_CREDENTIALS_FILE_STG_DEV = 'credentials_staging_develop';

    /**
     * @param ...$awsDirOrConfigurationFile
     * @return string
     */
    private static function getAWSConfigurationPath(...$awsDirOrConfigurationFile): string
    {
        return DirHelper::join(DirHelper::getHomeDir(), ...$awsDirOrConfigurationFile);
    }

    /**
     * save to .env file or custom name
     * @return void
     */
    public static function getSecretEnv(string $secretName, string $customENVName = null)
    {
        $ENVName = $customENVName ?? '.env'; // default
        // remove old file
        if (is_file(DirHelper::getWorkingDir($ENVName))) {
            (new Process("Delete old env file", DirHelper::getWorkingDir(), [
                sprintf("rm -f %s", DirHelper::getWorkingDir($ENVName)),
            ]))
                ->execMultiInWorkDir()->printOutput();
        }
        // get
        exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output text  > %s", $secretName, $ENVName));
        // validate result
        $isSuccess = is_file(DirHelper::getWorkingDir($ENVName)) && trim(file_get_contents(DirHelper::getWorkingDir($ENVName)));
        self::LineNew()->printCondition($isSuccess,
            "get secret '$secretName' successfully and save at '$ENVName'",
            "get secret '$secretName' failed"
        );
        if (!$isSuccess) exit(1);
    }

    /**
     * also show version first
     * should run with command in shell:
     *      val "$(myops load-env-ops)"
     *
     * @return string
     */
    public static function loadOpsEnvAndHandleMore(): string
    {
        $opsEnvSecretName = 'env-ops';
        $opsEnvData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $opsEnvSecretName)));
        //
        return sprintf("#!/bin/bash\n%s\n%s", $opsEnvData, MyOps::getShellData());
    }

    /**
     * required:
     * - data store in SecretManager > env-ops > field GITHUB_PERSONAL_ACCESS_TOKEN
     * - AWS credential have permission to get env-ops
     * @param string $fieldName
     * @return string|null
     */
    public static function getValueEnvOpsSecretManager(string $fieldName): ?string
    {
        $opsEnvSecretName = 'env-ops';
        $opsEnvData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $opsEnvSecretName)));
        //
        $opsEnvDataArr = explode(PHP_EOL, $opsEnvData);
        $line = array_filter($opsEnvDataArr, function ($item) use ($fieldName) {
            return StrHelper::contains($item, $fieldName);
        });
        return trim(str_replace("export $fieldName=", '', reset($line)), "'\"");
    }

    /**
     * works:
     * - get tags name from SSM
     * - build a version file (.zip)
     * - upload a version file to S3 bucket
     * - update ELB environment with new version
     * @return void
     */
    public static function ELBUpdateVersion()
    {
        try {
            // === validate ===
            if (!ValidationHelper::validateEnvVars([
                'BRANCH', "REPOSITORY",
                'ENV', 'ECR_REPO_API', 'S3_EB_APP_VERSION_BUCKET_NAME',
                'EB_APP_VERSION_FOLDER_NAME', 'EB_ENVIRONMENT_NAME',
                'EB_2ND_DISK_SIZE',
                'EB_MAIL_CATCHER_PORT', // maybe remove after email-serivce
            ])) {
                exitApp(ERROR_END);
            }
            // === handle ===
            self::LineNew()->printSeparatorLine()
                ->setTagMultiple([getenv('REPOSITORY'), getenv('BRANCH')])
                ->printTitle("Handle ELB version - ELASTIC BEANSTALK");
            //    vars
            $ENV = getenv('ENV');
            //    handle ELB version dir
            if (is_dir(DirHelper::getWorkingDir(self::ELB_TEMP_DIR))) {
                $commands[] = sprintf("rm -rf '%s'", DirHelper::getWorkingDir(self::ELB_TEMP_DIR));
            }
            $commands[] = sprintf("mkdir -p '%s/%s'", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), self::ELB_EBEXTENSIONS_DIR);
            (new Process("handle ELB version directory", DirHelper::getWorkingDir(), $commands))
                ->execMultiInWorkDir()->printOutput();
            //   handle SSM and get image tag values
            //        SSM tag names
            $SSM_ENV_TAG_API_NAME = "/$ENV/TAG_API_NAME";
            $SSM_ENV_TAG_INVOICE_SERVICE_NAME = "/$ENV/TAG_INVOICE_SERVICE_NAME";
            $SSM_ENV_TAG_PAYMENT_SERVICE_NAME = "/$ENV/TAG_PAYMENT_SERVICE_NAME";
            $SSM_ENV_TAG_INTEGRATION_API_NAME = "/$ENV/TAG_INTEGRATION_API_NAME";
            $imageTagValues = (new Process("get image tag value from AWS SSM", DirHelper::getWorkingDir(), [
                "aws ssm get-parameters --names '$SSM_ENV_TAG_API_NAME' '$SSM_ENV_TAG_INVOICE_SERVICE_NAME' '$SSM_ENV_TAG_PAYMENT_SERVICE_NAME' '$SSM_ENV_TAG_INTEGRATION_API_NAME' --output json"
            ]))->execMulti()->getOutputStrAll();
            foreach (json_decode($imageTagValues, true)['Parameters'] as $paramObj) {
                switch ($paramObj['Name']) {
                    case $SSM_ENV_TAG_API_NAME:
                        $TAG_API_NAME = $paramObj['Value'];
                        break;
                    case $SSM_ENV_TAG_INVOICE_SERVICE_NAME:
                        $TAG_INVOICE_SERVICE_NAME = $paramObj['Value'];
                        break;
                    case $SSM_ENV_TAG_PAYMENT_SERVICE_NAME:
                        $TAG_PAYMENT_SERVICE_NAME = $paramObj['Value'];
                        break;
                    case $SSM_ENV_TAG_INTEGRATION_API_NAME:
                        $TAG_INTEGRATION_API_NAME = $paramObj['Value'];
                        break;
                    default:
                        // do nothing
                        break;
                }
            }
            //   handle Dockerrun.aws.json content
            $DockerrunContent = str_replace(
                [
                    "_MAIL_CATCHER_PORT_",
                    "ECR_REPO_IMAGE_URI_API", "ECR_REPO_IMAGE_URI_INVOICE_SERVICE",
                    "ECR_REPO_IMAGE_URI_PAYMENT_SERVICE", "ECR_REPO_IMAGE_URI_INTEGRATION_API"
                ],
                [
                    getenv('EB_MAIL_CATCHER_PORT'),
                    sprintf("%s:%s", getenv('ECR_REPO_API'), $TAG_API_NAME),
                    sprintf("%s:%s", getenv('ECR_REPO_INVOICE_SERVICE'), $TAG_INVOICE_SERVICE_NAME),
                    sprintf("%s:%s", getenv('ECR_REPO_PAYMENT_SERVICE'), $TAG_PAYMENT_SERVICE_NAME),
                    sprintf("%s:%s", getenv('ECR_REPO_INTEGRATION_API'), $TAG_INTEGRATION_API_NAME)
                ],
                MyOps::getELBTemplate()["DockerrunTemplate"]
            );
            //    write files
            file_put_contents(
                sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME),
                str_replace("_2ND_DISK_SIZE_", getenv('EB_2ND_DISK_SIZE'), MyOps::getELBTemplate()["blockdeviceTemplate"])
            );
            file_put_contents(sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME), $DockerrunContent);
            //    validate configs files again
            //        .ebextensions/blockdevice-xvdcz.config
            $blockdeviceConfigContent = file_get_contents(sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME));
            self::LineNew()->printSubTitle(".ebextensions/blockdevice-xvdcz.config")->print($blockdeviceConfigContent);
            if (!StrHelper::contains($blockdeviceConfigContent, getenv('EB_2ND_DISK_SIZE'))) {
                self::LineTag(TagEnum::ERROR)->print(".ebextensions/blockdevice-xvdcz.config got an error");
                exitApp(ERROR_END);
            }
            //        Dockerrun.aws.json
            $DockerrunContentToCheckAgain = file_get_contents(sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME));
            self::LineNew()->printSubTitle("Dockerrun.aws.json")->print($DockerrunContentToCheckAgain);
            if (!StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_API'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_API_NAME)
                || !StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_INVOICE_SERVICE'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_INVOICE_SERVICE_NAME)
                || !StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_PAYMENT_SERVICE'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_PAYMENT_SERVICE_NAME)
                || !StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_INTEGRATION_API'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_INTEGRATION_API_NAME)
            ) {
                self::LineTag(TagEnum::ERROR)->print("Dockerrun.aws.json got an error");
                exitApp(ERROR_END);
            }
            //    create ELB version and update
            $EB_APP_VERSION_LABEL = sprintf("$ENV-$TAG_API_NAME-$TAG_INVOICE_SERVICE_NAME-$TAG_PAYMENT_SERVICE_NAME-$TAG_INTEGRATION_API_NAME-%sZ", (new DateTime())->format('Ymd-His'));
            (new Process("zip ELB version", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), [
                //    create .zip file
                sprintf("zip -r %s.zip Dockerrun.aws.json .ebextensions", $EB_APP_VERSION_LABEL),
                //    Copy to s3 and create eb application version | required to run in elb-version directory
                sprintf("aws s3 cp %s.zip s3://%s/%s/%s.zip || exit 1",
                    $EB_APP_VERSION_LABEL, getenv('S3_EB_APP_VERSION_BUCKET_NAME'),
                    getenv('EB_APP_VERSION_FOLDER_NAME'), $EB_APP_VERSION_LABEL
                ),
                //    create ELB application version
                sprintf("aws elasticbeanstalk create-application-version --application-name %s --version-label %s --source-bundle S3Bucket=%s,S3Key=%s/%s.zip > /dev/null || exit 1",
                    getenv('EB_APP_NAME'), $EB_APP_VERSION_LABEL,
                    getenv('S3_EB_APP_VERSION_BUCKET_NAME'), getenv('EB_APP_VERSION_FOLDER_NAME'),
                    $EB_APP_VERSION_LABEL
                ), // > /dev/null : disabled output
                //    update EB environment
                sprintf("aws elasticbeanstalk update-environment --environment-name %s --version-label %s > /dev/null",
                    getenv('EB_ENVIRONMENT_NAME'), $EB_APP_VERSION_LABEL
                ), // > /dev/null : disabled output
            ]))->execMultiInWorkDir()->printOutput();
            //    Check new service healthy every X seconds | timeout = 20 minutes
            //        on 08/28/2023: Elastic Beanstalk environment update about 4 - 7 minutes
            $environmentUpdateStartingTime = new DateTime();
            for ($minute = 3; $minute >= 1; $minute--) {
                self::LineNew()->print("Wait $minute minutes for the ELB environment does the update, and add some lines of logs...");
                sleep(60);
            }
            //        do check | ELB logs
            for ($i = 1; $i <= 40; $i++) {
                self::LineNew()->print("Healthcheck the $i time");
                $lastELBLogs = (new Process("get last ELB logs", DirHelper::getWorkingDir(), [
                    sprintf("aws elasticbeanstalk describe-events --application-name %s --environment-name %s --query 'Events[].Message' --output json --max-items 100 --start-time %s",
                        getenv('EB_APP_NAME'), getenv('EB_ENVIRONMENT_NAME'), $environmentUpdateStartingTime->format('Y-m-d\TH:i:s\Z')
                    ),
                ]))->execMulti()->getOutputStrAll();

                if (collect(json_decode($lastELBLogs))->contains(self::ELB_LOG_UPDATE_SUCCESSFULLY)) {
                    self::LineTag(TagEnum::SUCCESS)->print(self::ELB_LOG_UPDATE_SUCCESSFULLY);
                    exitApp(SUCCESS_END);
                } else if (collect(json_decode($lastELBLogs))->contains(self::ELB_LOG_UPDATE_FAILED)) {
                    self::LineTag(TagEnum::ERROR)->print(self::ELB_LOG_UPDATE_FAILED);
                    exitApp(ERROR_END);
                } else {
                    self::LineNew()->print("Environment is still not healthy");
                    // check again after X seconds
                    sleep(30);
                }
            }
            //             case timeout
            self::LineTag(TagEnum::ERROR)->print("Deployment got a timeout result");
            exitApp(ERROR_END);
        } catch (Exception $ex) {
            self::LineTag(TagEnum::ERROR)->print($ex->getMessage());
            exitApp(ERROR_END);
        }
    }

    /**
     * require ENVs: BRANCH
     * @return void
     */
    public static function automateToSwitchAWSCredentialForCICD()
    {
        // pre-handle
        //    select credential key based on branch
        $AWSCredentialFile = collect(GitHubEnum::PRODUCTION_REPOSITORIES)->contains(getenv('REPOSITORY'))
        && collect(GitHubEnum::PRODUCTION_BRANCHES)->contains(getenv('BRANCH'))
            ? self::AWS_CONFIGURATION_CREDENTIALS_FILE_PROD : self::AWS_CONFIGURATION_CREDENTIALS_FILE_STG_DEV;
        // validate
        DirHelper::validateDirOrFileExisting(ValidationTypeEnum::EXISTS, self::getAWSConfigurationPath(self::AWS_CONFIGURATION_DIR), $AWSCredentialFile);
        // handle
        (new Process("Override AWS Credential Key", self::getAWSConfigurationPath(self::AWS_CONFIGURATION_DIR), [
            sprintf("cp -f %s %s",
                self::getAWSConfigurationPath(self::AWS_CONFIGURATION_DIR, $AWSCredentialFile),
                self::getAWSConfigurationPath(self::AWS_CONFIGURATION_DIR, self::AWS_CONFIGURATION_CREDENTIALS_FILE)
            )
        ]))->execMultiInWorkDir(true)->printOutput();
        // validate the result
        DirHelper::validateFileContainsText(
            self::getAWSConfigurationPath(self::AWS_CONFIGURATION_DIR, self::AWS_CONFIGURATION_CREDENTIALS_FILE),
            trim(substr(file_get_contents(self::getAWSConfigurationPath(self::AWS_CONFIGURATION_DIR, $AWSCredentialFile)), -6), "\n")
        );
    }
}

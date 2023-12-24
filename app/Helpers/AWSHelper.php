<?php

namespace app\Helpers;

use app\app;
use app\Objects\Process;
use DateTime;
use Exception;

class AWSHelper
{
    const ELB_TEMP_DIR = "tmp/elb-version";
    const ELB_EBEXTENSIONS_DIR = ".ebextensions"; // should place at inside elb version dir
    const ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME = "blockdevice-xvdcz.config";
    const ELB_DOCKERRUN_FILE_NAME = "Dockerrun.aws.json";
    const ELB_LOG_UPDATE_SUCCESSFULLY = "Environment update completed successfully.";
    const ELB_LOG_UPDATE_FAILED = "Failed to deploy application.";

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
        TextHelper::messageSUCCESS("get secret '$secretName' success and save at '$ENVName'");
    }

    /**
     * should run with command in shell:
     *      val "$(php _ops/lib load-env-ops)"
     *
     * @return string
     */
    public static function loadOpsEnvAndHandleMore(): string
    {
        $opsEnvSecretName = 'env-ops';
        $opsEnvData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $opsEnvSecretName)));
        //
        return sprintf("#!/bin/bash\n%s\n%s", $opsEnvData, app::getShellData());
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
            if (!OpsHelper::validateEnvVars([
                'BRANCH', "REPOSITORY",
                'ENV', 'ECR_REPO_API', 'S3_EB_APP_VERSION_BUCKET_NAME',
                'EB_APP_VERSION_FOLDER_NAME', 'EB_ENVIRONMENT_NAME',
                'EB_2ND_DISK_SIZE',
                'EB_MAIL_CATCHER_PORT', // maybe remove after email-serivce
            ])) {
                exit(1); // END
            }
            // === handle ===
            TextHelper::messageSeparate();
            TextHelper::messageTitle(sprintf("[%s] [%s] Handle ELB version - ELASTIC BEANSTALK", getenv('REPOSITORY'), getenv('BRANCH')));
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
                app::getELBTemplate()["DockerrunTemplate"]
            );
            //    write files
            file_put_contents(
                sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME),
                str_replace("_2ND_DISK_SIZE_", getenv('EB_2ND_DISK_SIZE'), app::getELBTemplate()["blockdeviceTemplate"])
            );
            file_put_contents(sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME), $DockerrunContent);
            //    validate configs files again
            //        .ebextensions/blockdevice-xvdcz.config
            $blockdeviceConfigContent = file_get_contents(sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME));
            TextHelper::message(".ebextensions/blockdevice-xvdcz.config");
            TextHelper::message($blockdeviceConfigContent);
            if (!STR::contains($blockdeviceConfigContent, getenv('EB_2ND_DISK_SIZE'))) {
                TextHelper::messageERROR(".ebextensions/blockdevice-xvdcz.config got an error");
                exit(1); // END
            }
            //        Dockerrun.aws.json
            $DockerrunContentToCheckAgain = file_get_contents(sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME));
            TextHelper::message("Dockerrun.aws.json");
            TextHelper::message($DockerrunContentToCheckAgain);
            if (!STR::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_API'))
                || !STR::contains($DockerrunContentToCheckAgain, $TAG_API_NAME)
                || !STR::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_INVOICE_SERVICE'))
                || !STR::contains($DockerrunContentToCheckAgain, $TAG_INVOICE_SERVICE_NAME)
                || !STR::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_PAYMENT_SERVICE'))
                || !STR::contains($DockerrunContentToCheckAgain, $TAG_PAYMENT_SERVICE_NAME)
                || !STR::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_INTEGRATION_API'))
                || !STR::contains($DockerrunContentToCheckAgain, $TAG_INTEGRATION_API_NAME)
            ) {
                TextHelper::messageERROR("Dockerrun.aws.json got an error");
                exit(1); // END
            }
            //    create ELB version and update
            $EB_APP_VERSION_LABEL = sprintf("$ENV-$TAG_API_NAME-$TAG_INVOICE_SERVICE_NAME-$TAG_PAYMENT_SERVICE_NAME-$TAG_INTEGRATION_API_NAME-%sZ", (new DateTime())->format('Ymd-His'));
            (new Process("zip ELB version", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), [
                //    create .zip file
                sprintf("zip -r %s.zip Dockerrun.aws.json .ebextensions", $EB_APP_VERSION_LABEL),
                //    Copy to s3 and create eb application version | required to run in elb-version directory
                sprintf("aws s3 cp %s.zip s3://%s/%s/%s.zip || exit 1",
                    $EB_APP_VERSION_LABEL,
                    getenv('S3_EB_APP_VERSION_BUCKET_NAME'),
                    getenv('EB_APP_VERSION_FOLDER_NAME'),
                    $EB_APP_VERSION_LABEL
                ),
                //    create ELB application version
                sprintf("aws elasticbeanstalk create-application-version --application-name %s --version-label %s --source-bundle S3Bucket=%s,S3Key=%s/%s.zip > /dev/null || exit 1",
                    getenv('EB_APP_NAME'),
                    $EB_APP_VERSION_LABEL,
                    getenv('S3_EB_APP_VERSION_BUCKET_NAME'),
                    getenv('EB_APP_VERSION_FOLDER_NAME'),
                    $EB_APP_VERSION_LABEL
                ), // > /dev/null : disabled output
                //    update EB environment
                sprintf("aws elasticbeanstalk update-environment --environment-name %s --version-label %s > /dev/null",
                    getenv('EB_ENVIRONMENT_NAME'),
                    $EB_APP_VERSION_LABEL
                ), // > /dev/null : disabled output
            ]))->execMultiInWorkDir()->printOutput();
            //    Check new service healthy every X seconds | timeout = 20 minutes
            //        08/28/2023: Elastic Beanstalk environment update about 4 - 7 minutes
            for ($minute = 3; $minute >= 1; $minute--) {
                TextHelper::message("Wait $minute minutes for the ELB environment does the update, and add some lines of logs...");
                sleep(60);
            }
            //        do check | ELB logs
            for ($i = 1; $i <= 40; $i++) {
                TextHelper::message("Healthcheck the $i time");
                $lastELBLogs = (new Process("get last ELB logs", DirHelper::getWorkingDir(), [
                    sprintf("aws elasticbeanstalk describe-events --application-name %s --environment-name %s --query 'Events[].Message' --output json --max-items 5",
                        getenv('EB_APP_NAME'),
                        getenv('EB_ENVIRONMENT_NAME')
                    ),
                ]))->execMulti()->getOutputStrAll();
                if (in_array(self::ELB_LOG_UPDATE_SUCCESSFULLY, json_decode($lastELBLogs))) {
                    TextHelper::messageSUCCESS(self::ELB_LOG_UPDATE_SUCCESSFULLY);
                    ServicesHelper::SlackMessage(['script path', 'slack', sprintf(
                        "[FINISH] [SUCCESS] %s just finished building and deploying the project %s",
                        getenv('DEVICE'), getenv('REPOSITORY')
                    )]);
                    exit(0); // END | successful
                } else if (in_array(self::ELB_LOG_UPDATE_FAILED, json_decode($lastELBLogs))) {
                    TextHelper::messageERROR(self::ELB_LOG_UPDATE_FAILED);
                    ServicesHelper::SlackMessage(['script path', 'slack', sprintf(
                        "[FINISH] [FAILURE 1 | Deploy failed] %s just finished building and deploying the project %s",
                        getenv('DEVICE'), getenv('REPOSITORY')
                    )]);
                    exit(1); // END | failed
                } else {
                    TextHelper::message("Environment is still not healthy");
                    // check again after X seconds
                    sleep(30);
                }
            }
            //             case timeout
            TextHelper::messageERROR("Deployment got a timeout result");
            ServicesHelper::SlackMessage(['script path', 'slack', sprintf(
                "[FINISH] [FAILURE 2 | Timeout] %s just finished building and deploying the project %s",
                getenv('DEVICE'), getenv('REPOSITORY')
            )]);
            exit(1); // END | failed
        } catch (Exception $ex) {
            TextHelper::messageERROR($ex->getMessage());
            exit(1); // END | exception error
        }
    }
}

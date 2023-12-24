<?php

namespace app\Helpers;

use app\app;
use app\Objects\Process;
use phpDocumentor\Reflection\Types\Self_;

class AWSHelper
{
    const ELB_TEMP_DIR = "tmp/elb-version";
    const ELB_EBEXTENSIONS_DIR = ".ebextensions"; // should place at inside elb version dir
    const ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME = "blockdevice-xvdcz.config";
    const ELB_DOCKERRUN_FILE_NAME = "Dockerrun.aws.json";

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
        var_dump(app::getELBTemplate()); // todo test
        TextHelper::messageTitle("Hanle ELB version - ELASTIC BEANSTALK");
        // === validate ===
        if (!OpsHelper::validateEnvVars([
            'ENV', 'ECR_REPO_API', 'S3_EB_APP_VERSION_BUCKET_NAME',
            'EB_APP_VERSION_FOLDER_NAME', 'EB_ENVIRONMENT_NAME'
        ])) {
            exit(1); // END
        }
        // === handle ===
        //    handle ELB version dir
        if (is_dir(DirHelper::getWorkingDir(self::ELB_TEMP_DIR))) {
            $commands[] = sprintf("rm -rf '%s'", DirHelper::getWorkingDir(self::ELB_TEMP_DIR));
        }
        $commands[] = sprintf("mkdir -p '%s/%s'", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), self::ELB_EBEXTENSIONS_DIR);
        (new Process("handle ELB version directory", DirHelper::getWorkingDir(), $commands))
            ->execMultiInWorkDir()->printOutput();
        //    write files
        file_put_contents(
            sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME),
            app::getELBTemplate()["blockdeviceTemplate"]
        );
        file_put_contents(
            sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME),
            app::getELBTemplate()["DockerrunTemplate"]
        );
        //    create .zip file
        (new Process("zip ELB version", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), [
            sprintf("zip -r %s.zip Dockerrun.aws.json .ebextensions", "todo-elb-label")
        ]))->execMultiInWorkDir()->printOutput();
    }
}

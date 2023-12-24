<?php

namespace App\Helpers;

use App\App;
use App\Objects\Process;

class AWSHelper
{
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
        return sprintf("#!/bin/bash\n%s\n%s", $opsEnvData, App::getShellData());
    }
}

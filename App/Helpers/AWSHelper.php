<?php

namespace App\Helpers;

class AWSHelper
{
    /**
     * save to .env file or custom name
     * @return void
     */
    public static function getSecretEnv(string $secretName, string $customENVName = null)
    {
        $ENVName = $customENVName ?? '.env'; // default
        exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output text  > %s", $secretName, $ENVName));
        TextHelper::messageSUCCESS("get secret '$secretName' success and save at '$ENVName'");
    }

    /**
     * should run with command in shell:
     *      val "$(php _ops/lib load-env-ops)"
     *
     * @param string $SHELL_HANDLE_ENV_OPS_DATA_BASE64
     * @return string
     */
    public static function loadOpsEnvAndHandleMore(string $SHELL_HANDLE_ENV_OPS_DATA_BASE64): string
    {
        $opsEnvSecretName = 'env-ops';
        $opsEnvData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $opsEnvSecretName)));

        // case release code
        if ($SHELL_HANDLE_ENV_OPS_DATA_BASE64) {
            $shellData = base64_decode($SHELL_HANDLE_ENV_OPS_DATA_BASE64);
            //
            // case development
        } else {
            $shellData = file_get_contents('App/_shell_/handle-env-ops.sh');
        }
        //
        return sprintf("#!/bin/bash\n%s\n%s", $opsEnvData, $shellData);
    }
}

<?php

namespace App\Helpers;

use App\Enum\GitHubEnum;

class OpsHelper
{
    public static function getS3WhiteListIpsDevelopment(): string
    {

        $NEXLE_IPS = [
            '115.73.208.177', // Nexle VPN
            '115.73.208.182', // Nexle HCM office - others
            '115.73.208.183', // Nexle HCM office - others
            '14.161.25.117', // Nexle HCM office - others
            '118.69.176.228', // Nexle DN office
        ];
        $GITHUB_RUNNER_SERVER_IP = '18.167.126.148';
        $EC2DevelopIp = exec("echo $(curl https://develop-api.engageplus.io/api/booking/IP-QYIa20HxwQ)");
        $EC2StagingIp = exec("echo $(curl https://staging-api.engageplus.io/api/booking/IP-QYIa20HxwQ)");
        //
        $S3_WHITELIST_IP_DEVELOPMENT = array_merge($NEXLE_IPS, [
            $GITHUB_RUNNER_SERVER_IP,
            $EC2DevelopIp,
            $EC2StagingIp
        ]);
        //
        return sprintf("\n\n%s\n\n", json_encode($S3_WHITELIST_IP_DEVELOPMENT));
    }

    public static function updateGitHubTokenAllProjects()
    {
        $GITHUB_PERSONAL_ACCESS_TOKEN_NEW = readline("Please input new GITHUB_PERSONAL_ACCESS_TOKEN? ");
        if (!$GITHUB_PERSONAL_ACCESS_TOKEN_NEW) {
            TextHelper::messageERROR("GitHub Personal Token should be string");
            exit(); // END
        }
//
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        TextHelper::message("WORKSPACE DIR = $workspaceDir");
        foreach (GitHubEnum::GITHUB_REPOSITORIES as $projectName => $GitHubUsername) {
            TextHelper::message(sprintf(" + Project '%s > %s': %s",
                $GitHubUsername,
                $projectName,
                is_dir(sprintf("%s/%s", $workspaceDir, $projectName)) ? "✔" : "X"
            ));
        }
// update token
        foreach (GitHubEnum::GITHUB_REPOSITORIES as $projectName => $GitHubUsername) {
            $projectDir = sprintf("%s/%s", $workspaceDir, $projectName);
            if (is_dir($projectDir)) {
                $output = null;
                $resultCode = null;
                exec(join(';', [
                    sprintf("cd \"%s\"", $projectDir), # jump into this directory
                    sprintf("git remote set-url origin https://%s@github.com/%s/%s.git", $GITHUB_PERSONAL_ACCESS_TOKEN_NEW, $GitHubUsername, $projectName),
                ]), $output, $resultCode);
                // print output
                foreach ($output as $line) {
                    TextHelper::message(sprintf("    + %s", $line));
                }
            }
        }
    }


}

<?php

namespace App\Helpers;

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

// key = GitHub project name, value =  GitHub username
        $listProjectsSupport = [
            'engage-api' => 'infohkengage',
            'engage-spa' => 'infohkengage',
            'engage-booking-api' => 'infohkengage',
            'engage-booking-spa' => 'infohkengage',
            'invoice-service' => 'infohkengage',
            'payment-service' => 'infohkengage',
            'integration-api' => 'infohkengage',
            'email-service' => 'infohkengage',
            //
            'engage-api-deploy' => 'infohkengage',
            //
            'engage-database-utils' => 'congnqnexlesoft',
            'ops-lib' => 'congnqnexlesoft',
            'docker-base-images' => 'congnqnexlesoft',
        ];

        $GITHUB_PERSONAL_ACCESS_TOKEN_NEW = readline("Please input new GITHUB_PERSONAL_ACCESS_TOKEN? ");
        if (!$GITHUB_PERSONAL_ACCESS_TOKEN_NEW) {
            echo "[ERROR] GitHub Personal Token should be string\n";
            exit();
        }
//
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        echo "WORKSPACE DIR = $workspaceDir\n";
        foreach ($listProjectsSupport as $projectName => $GitHubUsername) {
            echo sprintf(" + Project '%s > %s': %s\n",
                $GitHubUsername,
                $projectName,
                is_dir(sprintf("%s/%s", $workspaceDir, $projectName)) ? "✔" : "X"
            );
        }
// update token
        foreach ($listProjectsSupport as $projectName => $GitHubUsername) {
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
                    echo sprintf("    + %s\n", $line);
                }
            }
        }
    }


}

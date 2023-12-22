<?php

namespace App\Helpers;

use App\App;
use App\Enum\GitHubEnum;
use App\Objects\Process;
use SebastianBergmann\CodeCoverage\Report\Text;

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

    /**
     * sync new release code to project at _ops/lib
     * sync strategy:
     * - clone 'ops-lib' project at caches folder
     * - copy new lib file into project at _ops/lib
     */
    public static function sync()
    {
        // load env into PHP
        self::parseEnoughDataForSync(AWSHelper::loadOpsEnvAndHandleMore());
        // load caches of this source code
        GitHubHelper::handleCachesAndGit([
            'script path',
            'command-name', // param 1
            'ops-lib', // param 2, in this case is repository
            'main', // param 3, in this case is branch
        ]);
        // sync new lib
        $EngagePlusCachesRepositoryOpsLibDir = sprintf("%s/ops-lib", getenv('ENGAGEPLUS_CACHES_DIR'));
        (new Process("SYNC OPS LIB", DirHelper::getWorkingDir(), [
            'rm _ops/lib',
            sprintf(
                "cp -f '%s/_ops/lib' '%s/_ops/lib'",
                $EngagePlusCachesRepositoryOpsLibDir,
                DirHelper::getWorkingDir()
            ),
        ]))->execMultiInWorkDir()->printOutput();
        //
        TextHelper::messageSeparate();
        TextHelper::messageSUCCESS("sync done");
        // show open new session to show right version
        (new Process("CHECK A NEW VERSION", DirHelper::getWorkingDir(), [
            'php _ops/lib version'
        ]))->execMultiInWorkDir(true)->printOutput();
    }

    /**
     * need to get
     * - ENGAGEPLUS_CACHES_FOLDER
     * - ENGAGEPLUS_CACHES_DIR="$(php _ops/lib home-dir)/${ENGAGEPLUS_CACHES_FOLDER}"
     * - GITHUB_PERSONAL_ACCESS_TOKEN
     * and put to PHP env
     * @return void
     */
    private static function parseEnoughDataForSync(string $opsEnvAllData)
    {
        $tempArr = explode(PHP_EOL, $opsEnvAllData);
        foreach ($tempArr as $line) {
            if (strpos($line, "export ENGAGEPLUS_CACHES_FOLDER") !== false) {
                $key = explode('=', str_replace('export ', '', $line), 2)[0];
                $value = explode('=', str_replace('export ', '', $line), 2)[1];
                $value = trim($value, '"');
                putenv("$key=$value");
            }
            if (strpos($line, "export GITHUB_PERSONAL_ACCESS_TOKEN") !== false) {
                putenv(trim(str_replace('export ', '', $line), '"'));
            }
        }
        //
        putenv(sprintf("ENGAGEPLUS_CACHES_DIR=%s/%s", DirHelper::getHomeDir(), getenv('ENGAGEPLUS_CACHES_FOLDER')));
    }

    /**
     * do some post works:
     * - cleanup
     * @param array $argv
     * @return void
     */
    public static function postWork(): void
    {
        TextHelper::messageTitle("Post works");
        $isDoNothing = true;
        // === cleanup ===
        //    tmp dir (PHP project)
        if (is_dir(DirHelper::getWorkingDir('tmp'))) {
            (new Process("Remove tmp dir", DirHelper::getWorkingDir(), [
                sprintf("rm -rf '%s'", DirHelper::getWorkingDir('tmp'))
            ]))->execMultiInWorkDir()->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'tmp'", DirHelper::getWorkingDir()));
            TextHelper::messageCondition(!$checkTmpDir,
                'remove a tmp dir successfully', 'remove a tmp dir failure');
            //
            $isDoNothing = false;
        }
        //    dist dir (Angular project)
        if (is_dir(DirHelper::getWorkingDir('dist'))) {
            (new Process("Remove tmp dir", DirHelper::getWorkingDir(), [
                sprintf("rm -rf '%s'", DirHelper::getWorkingDir('dist'))
            ]))->execMultiInWorkDir()->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'dist'", DirHelper::getWorkingDir()));
            TextHelper::messageCondition(!$checkTmpDir,
                'remove a dist dir successfully', 'remove a dist dir failure');
            //
            $isDoNothing = false;
        }
        // === end cleanup ===
        //
        if ($isDoNothing) {
            TextHelper::message("do nothing");
        }
        TextHelper::messageSeparate();
    }

    public static function validate(array $argv)
    {
        switch ($argv[2] ?? null) {
            case 'branch':
                self::validateBranch();
                break;
            case 'docker':
                self::validateDocker();
                break;
            case 'device':
                self::validateDevice();
                break;
            default:
                TextHelper::messageERROR("invalid action, current support:  branch, docker, device");
                TextHelper::message("should be like eg:   php _ops/lib validate branch");
                break;
        }
    }

    /**
     * allow branches: develop, staging, master
     * should combine with exit 1 in shell:
     *     php _ops/lib validate branch || exit 1
     * @return void
     */
    private static function validateBranch()
    {
        if (in_array(getenv('BRANCH'), ['develop', 'staging', 'master'])) {
            TextHelper::messageSUCCESS('validation branch got OK result: ' . getenv('BRANCH'));
        } else {
            TextHelper::messageERROR(sprintf("Invalid branch to build | current branch is '%s'", getenv('BRANCH')));
            exit(1); // END app
        }
    }

    /**
     * Docker should is running
     * should combine with exit 1 in shell:
     *      php _ops/lib validate docker || exit 1
     */
    private static function validateDocker()
    {
        $dockerServer = exec("docker version | grep 'Server:'");
        if (trim($dockerServer)) {
            TextHelper::messageSUCCESS("Docker is running: $dockerServer");
        } else {
            TextHelper::messageERROR("Docker isn't running. Please start Docker app.");
            exit(1); // END app
        }
    }

    /**
     * should have env var: BRANCH
     *     php _ops/lib validate device || exit 1
     * @return void
     */
    private static function validateDevice()
    {
        if (getenv('DEVICE')) {
            TextHelper::messageSUCCESS('validation device got OK result: ' . getenv('DEVICE'));
        } else {
            TextHelper::messageERROR("Invalid device | should pass in your command");
            exit(1); // END app
        }
    }
}

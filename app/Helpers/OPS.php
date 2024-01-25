<?php

namespace app\Helpers;

use app\Enum\GitHubEnum;
use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\PostWorkEnum;
use app\Enum\TagEnum;
use app\Enum\UIEnum;
use app\Enum\ValidationTypeEnum;
use app\Objects\Process;

/**
 * This is Ops helper
 */
class OPS
{
    const COMPOSER_CONFIG_GITHUB_AUTH_FILE = 'auth.json';

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
            TEXT::tag(TagEnum::ERROR)->message("GitHub Personal Token should be string");
            exit(); // END
        }
//
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        TEXT::new()->message("WORKSPACE DIR = $workspaceDir");
        foreach (GitHubEnum::GITHUB_REPOSITORIES as $projectName => $GitHubUsername) {
            TEXT::icon(IconEnum::PLUS)->message("Project '%s > %s': %s",
                $GitHubUsername,
                $projectName,
                is_dir(sprintf("%s/%s", $workspaceDir, $projectName)) ? "✔" : "X"
            );
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
                    TEXT::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::PLUS)->message($line);
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
        TEXT::new()->messageTitle(__FUNCTION__);
        // load env into PHP
        self::parseEnoughDataForSync(AWS::loadOpsEnvAndHandleMore());
        // load caches of this source code
        GITHUB::handleCachesAndGit([
            'script path',
            'command-name', // param 1
            'ops-lib', // param 2, in this case is repository
            'main', // param 3, in this case is branch
        ]);
        // sync new lib
        $EngagePlusCachesRepositoryOpsLibDir = sprintf("%s/ops-lib", getenv('ENGAGEPLUS_CACHES_DIR'));
        (new Process("SYNC OPS LIB", DIR::getWorkingDir(), [
            'rm _ops/lib',
            sprintf(
                "cp -f '%s/_ops/lib' '%s/_ops/lib'",
                $EngagePlusCachesRepositoryOpsLibDir,
                DIR::getWorkingDir()
            ),
        ]))->execMultiInWorkDir()->printOutput();
        //
        TEXT::new()->messageSeparate()
            ->setTag(TagEnum::SUCCESS)->message("sync done");
        TEXT::new()->messageSeparate();
        // show open new session to show right version
        (new Process("CHECK A NEW VERSION", DIR::getWorkingDir(), [
            'php _ops/lib version'
        ]))->execMultiInWorkDir(true)->printOutput();
        //
        TEXT::new()->messageSeparate();
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
        putenv(sprintf("ENGAGEPLUS_CACHES_DIR=%s/%s", DIR::getHomeDir(), getenv('ENGAGEPLUS_CACHES_FOLDER')));
    }

    /**
     * do some post works:
     * - cleanup
     * @param array $argv
     * @return void
     */
    public static function postWork(array $argv): void
    {
        // === param ===
        $isSkipCheckDir = ($argv[2] ?? null) === PostWorkEnum::SKIP_CHECK_DIR;
        //
        TEXT::new()->messageTitle("Post works");
        if($isSkipCheckDir){
            TEXT::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)
                ->message("skip check execution directory");
        }
        $isDoNothing = true;
        // === cleanup ===
        //    clear .env, .project-config
        if (getenv('ENGAGEPLUS_CACHES_FOLDER')
            && STR::contains(DIR::getWorkingDir(), getenv('ENGAGEPLUS_CACHES_FOLDER'))) {
            //        .env
            if (is_file(DIR::getWorkingDir('.env'))) {
                (new Process("Remove .env", DIR::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DIR::getWorkingDir('.env'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '.env'", DIR::getWorkingDir()));
                TEXT::new()->messageCondition(!$checkTmpDir,
                    "remove '.env' file successfully", "remove '.env' file failed");
                //
                $isDoNothing = false;
            }
            //        .env
            if (is_file(DIR::getWorkingDir('.project-config'))) {
                (new Process("Remove .project-config", DIR::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DIR::getWorkingDir('.project-config'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '.project-config'", DIR::getWorkingDir()));
                TEXT::new()->messageCondition(!$checkTmpDir,
                    "remove a '.project-config' file successfully", "remove a '.project-config' file failed");
                //
                $isDoNothing = false;
            }
        }
        //    tmp dir (PHP project)
        if (is_dir(DIR::getWorkingDir('tmp'))) {
            (new Process("Remove tmp dir", DIR::getWorkingDir(), [
                sprintf("rm -rf '%s'", DIR::getWorkingDir('tmp'))
            ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'tmp'", DIR::getWorkingDir()));
            TEXT::new()->messageCondition(!$checkTmpDir,
                'remove a tmp dir successfully', 'remove a tmp dir failure');
            //
            $isDoNothing = false;
        }
        //    dist dir (Angular project)
        if (is_dir(DIR::getWorkingDir('dist'))) {
            (new Process("Remove dist dir", DIR::getWorkingDir(), [
                sprintf("rm -rf '%s'", DIR::getWorkingDir('dist'))
            ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'dist'", DIR::getWorkingDir()));
            TEXT::new()->messageCondition(!$checkTmpDir,
                'remove a dist dir successfully', 'remove a dist dir failure');
            //
            $isDoNothing = false;
        }
        //    composer config file: auth.json
        if (is_file(DIR::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE))) {
            $authJsonContent = file_get_contents(DIR::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE));
            if (STR::contains($authJsonContent, "github-oauth") && STR::contains($authJsonContent, "github.com")) {
                (new Process("Remove composer config file", DIR::getWorkingDir(), [
                    sprintf("rm -f '%s'", DIR::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '%s'", DIR::getWorkingDir(), self::COMPOSER_CONFIG_GITHUB_AUTH_FILE));
                TEXT::new()->messageCondition(
                    !$checkTmpDir,
                    sprintf("remove file '%s' successfully", self::COMPOSER_CONFIG_GITHUB_AUTH_FILE),
                    sprintf("remove file '%s' failed", self::COMPOSER_CONFIG_GITHUB_AUTH_FILE)
                );
                //
                $isDoNothing = false;
            }
        }
        //    dangling Docker images / <none> Docker images
        if (DOCKER::isDockerInstalled()) {
            if (DOCKER::isDanglingImages()) {
                DOCKER::removeDanglingImages();
                //
                $isDoNothing = false;
            }
        }

        // === end cleanup ===
        //
        if ($isDoNothing) {
            TEXT::new()->message("do nothing");
        }
        TEXT::new()->messageSeparate();
    }

    public static function clearOpsDir(): void
    {
        TEXT::new()->messageTitle("Clear _ops directory");
        (new Process("Clear _ops directory", DIR::getWorkingDir(), [
            sprintf("rm -rf '%s'", DIR::getWorkingDir('_ops'))
        ]))->execMultiInWorkDir(true)->printOutput();
        // validate result
        $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '_ops'", DIR::getWorkingDir()));
        TEXT::new()->messageCondition(!$checkTmpDir, "clear _ops dir successfully", "clear _ops dir failed");
    }

    /**
     * also notify an error message,
     * eg: ['VAR1', 'VAR2']
     * @param array $envVars
     * @return bool
     */
    public static function validateEnvVars(array $envVars): bool
    {
        $envVarsMissing = [];
        foreach ($envVars as $envVar) {
            if (!getenv($envVar)) $envVarsMissing[] = $envVar;
        }
        if (count($envVarsMissing) > 0) {
            TEXT::tagMultiple([TagEnum::ERROR, TagEnum::ENV])->message("missing %s", join(" or ", $envVarsMissing));
            return false; // END | case error
        }
        return true; // END | case OK
    }

    public static function validate(array $argv)
    {
        switch ($argv[2] ?? null) {
            case ValidationTypeEnum::BRANCH:
                self::validateBranch();
                break;
            case ValidationTypeEnum::DOCKER:
                self::validateDocker();
                break;
            case ValidationTypeEnum::DEVICE:
                self::validateDevice();
                break;
            case ValidationTypeEnum::FILE_CONTAINS_TEXT:
                self::validateFileContainsText($argv);
                break;
            default:
                TEXT::tag(TagEnum::ERROR)->message("invalid action, current support:  %s", join(", ", ValidationTypeEnum::SUPPORT_LIST))
                    ->message("should be like eg:   php _ops/lib validate branch");
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
            TEXT::tag(TagEnum::SUCCESS)->message("validation branch got OK result: %s", getenv('BRANCH'));
        } else {
            TEXT::tag(TagEnum::ERROR)->message("Invalid branch to build | current branch is '%s'", getenv('BRANCH'));
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
            TEXT::tag(TagEnum::SUCCESS)->message("Docker is running: $dockerServer");
        } else {
            TEXT::tag(TagEnum::ERROR)->message("Docker isn't running. Please start Docker app.");
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
            TEXT::tag(TagEnum::SUCCESS)->message("validation device got OK result: %s", getenv('DEVICE'));
        } else {
            TEXT::tag(TagEnum::ERROR)->message("Invalid device | should pass in your command");
            exit(1); // END app
        }
    }

    private static function validateFileContainsText(array $argv)
    {
        // validate
        $filePath = $argv[3] ?? null;
        $searchTextArr = [];
        for ($i = 4; $i < 20; $i++) {
            if (count($argv) > $i)
                if ($argv[$i]) {
                    $searchTextArr[] = $argv[$i];
                }
        }
        if (!$filePath || !count($searchTextArr)) {
            Text::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])->message("missing filePath or searchText (can path multiple searchText1 searchText2)");
            exit(1);
        }
        if (!is_file($filePath)) {
            Text::tag(TagEnum::ERROR)->message("'%s' does not exist", $filePath);
            exit(1);
        }
        // handle
        $fileContent = file_get_contents($filePath);
        $validationResult = [];
        foreach ($searchTextArr as $searchText) {
            $validationResult[] = [
                'searchText' => $searchText,
                'isContains' => STR::contains($fileContent, $searchText)
            ];
        }
        $amountValidationPass = count(array_filter($validationResult, function ($item) {
            return $item['isContains'];
        }));
        if ($amountValidationPass === count($searchTextArr)) {
            Text::tagMultiple([TagEnum::VALIDATION, TagEnum::SUCCESS])->message("file '%s' contains text(s): '%s'", $filePath, join("', '", $searchTextArr));
        } else {
            Text::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->message("file '%s' does not contains (some) text(s):", $filePath);
            foreach ($validationResult as $result) {
                TEXT::indent(IndentLevelEnum::ITEM_LINE)
                    ->setIcon($result['isContains'] ? IconEnum::CHECK : IconEnum::X)
                    ->setColor($result['isContains'] ? UIEnum::COLOR_GREEN : UIEnum::COLOR_RED)
                    ->message($result['searchText']);
            }
            exit(1);
        }
    }
}

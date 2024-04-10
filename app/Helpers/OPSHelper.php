<?php

namespace app\Helpers;

use app\Classes\GitHubRepositoryInfo;
use app\Enum\GitHubEnum;
use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\PostWorkEnum;
use app\Enum\TagEnum;
use app\Enum\UIEnum;
use app\Enum\ValidationTypeEnum;
use app\Classes\Process;

/**
 * This is Ops helper
 */
class OPSHelper
{
    const COMPOSER_CONFIG_GITHUB_AUTH_FILE = 'auth.json';

    public static function getS3WhiteListIpsDevelopment(): string
    {

        $NEXLE_IPS = [
            '115.73.208.177', // Nexle VPN
            '115.73.208.182', // Nexle HCM office - IP 1
            '115.73.208.183', // Nexle HCM office - IP 2
            '14.161.25.117', // Nexle HCM office - IP 3
            '113.160.235.76', // Nexle DN office NEW (2 2024)
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
            TextHelper::tag(TagEnum::ERROR)->message("GitHub Personal Token should be string");
            exit(); // END
        }
//
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        TextHelper::new()->message("WORKSPACE DIR = $workspaceDir");
//        foreach (GitHubEnum::GITHUB_REPOSITORIES as $projectName => $GitHubUsername) {
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            TextHelper::icon(IconEnum::PLUS)->message("Project '%s > %s': %s",
                $repoInfo->getUsername(), $repoInfo->getRepositoryName(),
                is_dir(sprintf("%s/%s", $workspaceDir, $repoInfo->getRepositoryName())) ? "✔" : "X"
            );
        }
// update token
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            $projectDir = sprintf("%s/%s", $workspaceDir, $repoInfo->getRepositoryName());
            if (is_dir($projectDir)) {
                $output = null;
                $resultCode = null;
                exec(join(';', [
                    sprintf("cd \"%s\"", $projectDir), # jump into this directory
                    sprintf("git remote set-url origin https://%s@github.com/%s/%s.git", $GITHUB_PERSONAL_ACCESS_TOKEN_NEW, $repoInfo->getUsername(), $repoInfo->getRepositoryName()),
                ]), $output, $resultCode);
                // print output
                foreach ($output as $line) {
                    TextHelper::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::PLUS)->message($line);
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
        TextHelper::new()->messageTitle(__FUNCTION__);
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
        TextHelper::new()->messageSeparate()
            ->setTag(TagEnum::SUCCESS)->message("sync done");
        TextHelper::new()->messageSeparate();
        // show open new session to show right version
        (new Process("CHECK A NEW VERSION", DirHelper::getWorkingDir(), [
            'php _ops/lib version'
        ]))->execMultiInWorkDir(true)->printOutput();
        //
        TextHelper::new()->messageSeparate();
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
    public static function postWork(array $argv): void
    {
        // === param ===
        $isSkipCheckDir = ($argv[2] ?? null) === PostWorkEnum::SKIP_CHECK_DIR;
        //
        TextHelper::new()->messageTitle("Post works");
        if ($isSkipCheckDir) {
            TextHelper::indent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)
                ->message("skip check execution directory");
        }
        $isDoNothing = true;
        // === cleanup ===
        //    clear .env, .conf-ryt
        if (getenv('ENGAGEPLUS_CACHES_FOLDER')
            && StrHelper::contains(DirHelper::getWorkingDir(), getenv('ENGAGEPLUS_CACHES_FOLDER'))) {
            //        .env
            if (is_file(DirHelper::getWorkingDir('.env'))) {
                (new Process("Remove .env", DirHelper::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DirHelper::getWorkingDir('.env'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '.env'", DirHelper::getWorkingDir()));
                TextHelper::new()->messageCondition(!$checkTmpDir,
                    "remove '.env' file successfully", "remove '.env' file failed");
                //
                $isDoNothing = false;
            }
            //        .conf-ryt
            if (is_file(DirHelper::getWorkingDir('.conf-ryt'))) {
                (new Process("Remove .conf-ryt", DirHelper::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DirHelper::getWorkingDir('.conf-ryt'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '.conf-ryt'", DirHelper::getWorkingDir()));
                TextHelper::new()->messageCondition(!$checkTmpDir,
                    "remove a '.conf-ryt' file successfully", "remove a '.conf-ryt' file failed");
                //
                $isDoNothing = false;
            }
            //        [payment-service] payment-credentials.json
            if (is_file(DirHelper::getWorkingDir('payment-credentials.json'))) {
                (new Process("Remove 'payment-credentials.json'", DirHelper::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DirHelper::getWorkingDir('payment-credentials.json'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'payment-credentials.json'", DirHelper::getWorkingDir()));
                TextHelper::new()->messageCondition(!$checkTmpDir,
                    "remove a 'payment-credentials.json' file successfully", "remove a 'payment-credentials.json' file failed");
                //
                $isDoNothing = false;
            }
        }
        //    tmp dir (PHP project)
        if (is_dir(DirHelper::getWorkingDir('tmp'))) {
            (new Process("Remove tmp dir", DirHelper::getWorkingDir(), [
                sprintf("rm -rf '%s'", DirHelper::getWorkingDir('tmp'))
            ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'tmp'", DirHelper::getWorkingDir()));
            TextHelper::new()->messageCondition(!$checkTmpDir,
                'remove a tmp dir successfully', 'remove a tmp dir failure');
            //
            $isDoNothing = false;
        }
        //    dist dir (Angular project)
        if (is_dir(DirHelper::getWorkingDir('dist'))) {
            (new Process("Remove dist dir", DirHelper::getWorkingDir(), [
                sprintf("rm -rf '%s'", DirHelper::getWorkingDir('dist'))
            ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'dist'", DirHelper::getWorkingDir()));
            TextHelper::new()->messageCondition(!$checkTmpDir,
                'remove a dist dir successfully', 'remove a dist dir failure');
            //
            $isDoNothing = false;
        }
        //    composer config file: auth.json
        if (is_file(DirHelper::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE))) {
            $authJsonContent = file_get_contents(DirHelper::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE));
            if (StrHelper::contains($authJsonContent, "github-oauth") && StrHelper::contains($authJsonContent, "github.com")) {
                (new Process("Remove composer config file", DirHelper::getWorkingDir(), [
                    sprintf("rm -f '%s'", DirHelper::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '%s'", DirHelper::getWorkingDir(), self::COMPOSER_CONFIG_GITHUB_AUTH_FILE));
                TextHelper::new()->messageCondition(
                    !$checkTmpDir,
                    sprintf("remove file '%s' successfully", self::COMPOSER_CONFIG_GITHUB_AUTH_FILE),
                    sprintf("remove file '%s' failed", self::COMPOSER_CONFIG_GITHUB_AUTH_FILE)
                );
                //
                $isDoNothing = false;
            }
        }
        //    dangling Docker images / <none> Docker images
        if (DockerHelper::isDockerInstalled()) {
            if (DockerHelper::isDanglingImages()) {
                DockerHelper::removeDanglingImages();
                //
                $isDoNothing = false;
            }
        }

        // === end cleanup ===
        //
        if ($isDoNothing) {
            TextHelper::new()->message("do nothing");
        }
        TextHelper::new()->messageSeparate();
    }

    public static function clearOpsDir(): void
    {
        TextHelper::new()->messageTitle("Clear _ops directory");
        (new Process("Clear _ops directory", DirHelper::getWorkingDir(), [
            sprintf("rm -rf '%s'", DirHelper::getWorkingDir('_ops'))
        ]))->execMultiInWorkDir(true)->printOutput();
        // validate result
        $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '_ops'", DirHelper::getWorkingDir()));
        TextHelper::new()->messageCondition(!$checkTmpDir, "clear _ops dir successfully", "clear _ops dir failed");
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
            TextHelper::tagMultiple([TagEnum::ERROR, TagEnum::ENV])->message("missing %s", join(" or ", $envVarsMissing));
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
                TextHelper::tag(TagEnum::ERROR)->message("invalid action, current support:  %s", join(", ", ValidationTypeEnum::SUPPORT_LIST))
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
            TextHelper::tag(TagEnum::SUCCESS)->message("validation branch got OK result: %s", getenv('BRANCH'));
        } else {
            TextHelper::tag(TagEnum::ERROR)->message("Invalid branch to build | current branch is '%s'", getenv('BRANCH'));
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
            TextHelper::tag(TagEnum::SUCCESS)->message("Docker is running: $dockerServer");
        } else {
            TextHelper::tag(TagEnum::ERROR)->message("Docker isn't running. Please start Docker app.");
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
            TextHelper::tag(TagEnum::SUCCESS)->message("validation device got OK result: %s", getenv('DEVICE'));
        } else {
            TextHelper::tag(TagEnum::ERROR)->message("Invalid device | should pass in your command");
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
            TextHelper::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])->message("missing filePath or searchText (can path multiple searchText1 searchText2)");
            exit(1);
        }
        if (!is_file($filePath)) {
            TextHelper::tag(TagEnum::ERROR)->message("'%s' does not exist", $filePath);
            exit(1);
        }
        // handle
        $fileContent = file_get_contents($filePath);
        $validationResult = [];
        foreach ($searchTextArr as $searchText) {
            $validationResult[] = [
                'searchText' => $searchText,
                'isContains' => StrHelper::contains($fileContent, $searchText)
            ];
        }
        $amountValidationPass = count(array_filter($validationResult, function ($item) {
            return $item['isContains'];
        }));
        if ($amountValidationPass === count($searchTextArr)) {
            TextHelper::tagMultiple([TagEnum::VALIDATION, TagEnum::SUCCESS])->message("file '%s' contains text(s): '%s'", $filePath, join("', '", $searchTextArr));
        } else {
            TextHelper::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->message("file '%s' does not contains (some) text(s):", $filePath);
            foreach ($validationResult as $result) {
                TextHelper::indent(IndentLevelEnum::ITEM_LINE)
                    ->setIcon($result['isContains'] ? IconEnum::CHECK : IconEnum::X)
                    ->setColor($result['isContains'] ? UIEnum::COLOR_GREEN : UIEnum::COLOR_RED)
                    ->message($result['searchText']);
            }
            exit(1);
        }
    }
}

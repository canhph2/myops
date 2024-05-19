<?php

namespace App\Helpers;

use App\Classes\Base\CustomCollection;
use App\Classes\GitHubRepositoryInfo;
use App\Classes\Process;
use App\Enum\AppInfoEnum;
use App\Enum\DevelopmentEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Enum\ValidationTypeEnum;
use App\Factories\ShellFactory;
use App\Services\SlackService;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

/**
 * This is Ops helper
 */
class OPSHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

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
            self::LineTag(TagEnum::ERROR)->print("GitHub Personal Token should be string");
            exit(); // END
        }
//
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        self::LineNew()->print("WORKSPACE DIR = $workspaceDir");
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            self::LineIcon(IconEnum::PLUS)->print("Project '%s > %s': %s",
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
                    self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::PLUS)->print($line);
                }
            }
        }
    }

    /**
     * - sync new release code to caches dir in machine '~/.caches_engageplus/myops'
     * - create an alias 'myops' link to the release file at '~/.caches_engageplus/myops/.release/MyOps.php'
     */
    public static function sync()
    {
        self::LineNew()->printTitle(__FUNCTION__);
        // load env into PHP
        self::parseEnoughDataForSync(AWSHelper::loadOpsEnvAndHandleMore());
        // load caches of this source code
        GitHubHelper::checkoutCaches(GitHubEnum::MYOPS, GitHubHelper::getCurrentBranch());
        // create an alias 'myops'
        $EngagePlusCachesRepositoryOpsAppReleasePath = sprintf("%s/myops/%s", getenv('ENGAGEPLUS_CACHES_DIR'), AppInfoEnum::RELEASE_PATH);
        self::createAlias($EngagePlusCachesRepositoryOpsAppReleasePath);
        //
        self::LineNew()->printSeparatorLine()
            ->setTag(TagEnum::SUCCESS)->print("sync done");
        // show open new session to show right version
        (new Process("CHECK A NEW VERSION", DirHelper::getWorkingDir(), [
            'myops version'
        ]))->execMultiInWorkDir(true)->printOutput();
        //
        self::LineNew()->printSeparatorLine();
    }

    /**
     * create alias of release app (this app) in shell configuration files
     *
     * @return void
     */
    private static function createAlias(string $OpsAppReleasePath)
    {
        $alias = sprintf("alias %s=\"php %s\"", AppInfoEnum::APP_MAIN_COMMAND, $OpsAppReleasePath);
        $shellConfigurationFiles = [
            DirHelper::getHomeDir('.zshrc'), // Mac
            DirHelper::getHomeDir('.bashrc'), // Ubuntu
        ];
        foreach ($shellConfigurationFiles as $shellConfigurationFile) {
            if (is_file($shellConfigurationFile)) {
                self::lineNew()->printSubTitle("create alias '%s' at '%s'", AppInfoEnum::APP_MAIN_COMMAND, $shellConfigurationFile);
                // already setup
                if (StrHelper::contains(file_get_contents($shellConfigurationFile), $alias)) {
                    self::lineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)->print("already setup alias '%s'", AppInfoEnum::APP_MAIN_COMMAND);
                } else {
                    // setup alias
                    //    remove old alias (wrong path, old date alias)
                    $oldAliases = StrHelper::findLinesContainsTextInFile($shellConfigurationFile, AppInfoEnum::APP_MAIN_COMMAND);
                    foreach ($oldAliases as $oldAlias) {
                        StrHelper::replaceTextInFile($oldAlias, '', $shellConfigurationFile);
                    }
                    //    add new alias
                    if (file_put_contents($shellConfigurationFile, $alias . PHP_EOL, FILE_APPEND)) {
                        self::lineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::CHECK)->print("adding alias done");
                    } else {
                        self::lineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::X)->print("adding alias failure");
                    }
                }
                // validate alias
                DirHelper::validateFileContainsText($shellConfigurationFile, $alias);
            }
        }
    }

    /**
     * Create an alias directly to the MyOps.php call this command, use to manually install
     *
     * @return void
     */
    public static function createAliasDirectly()
    {
        self::LineNew()->printTitle(__FUNCTION__);
        //
        self::createAlias(DirHelper::getScriptFullPath());
        //
        self::LineNew()->printSeparatorLine();
    }

    /**
     * need to get
     * - ENGAGEPLUS_CACHES_FOLDER
     * - ENGAGEPLUS_CACHES_DIR="$(myops home-dir)/${ENGAGEPLUS_CACHES_FOLDER}"
     * - GITHUB_PERSONAL_ACCESS_TOKEN
     * and put to PHP env
     * @return void
     */
    public static function parseEnoughDataForSync(string $opsEnvAllData)
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
     * please read help to see more details
     * @return string
     */
    public static function preWorkBashContentForEval(): string
    {
        return (new CustomCollection([
            AWSHelper::loadOpsEnvAndHandleMore(), // bash content
            '# == MyOps Process, create a new process ==',
            sprintf("export PROCESS_ID=%s", ProcessHelper::handleProcessStart()),
        ]))->join(PHP_EOL);
    }

    /**
     * please read help to see more details
     * @return void
     */
    public static function preWorkNormal(): void
    {
        // version
        AppInfoHelper::printVersion();
        // pre-work
        self::lineNew()->printTitle("Prepare Work (pre-work)");
        //    process id
        self::lineIcon(IconEnum::CHECK)->setColor(UIEnum::COLOR_GREEN)
            ->print("I have added a new process with PROCESS_ID = %s", getenv('PROCESS_ID'));
        //   send starting message to Slack
        if (SlackService::handleInputMessage()) {
            SlackService::sendMessageConsole();
        }

    }

    /**
     * please read help to see more details
     * @return void
     */
    public
    static function postWork(): void
    {
        // === param ===
        $isSkipCheckDir = (bool)self::input('skip-check-dir');
        //
        self::LineNew()->printTitle("Post works");
        if ($isSkipCheckDir) {
            self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)
                ->print("skip check execution directory");
        }
        // === cleanup ===
        $isDoSomeThing = DirHelper::removeFileOrDirInCachesDir(DevelopmentEnum::DOT_ENV);
        $isDoSomeThing = DirHelper::removeFileOrDirInCachesDir(DevelopmentEnum::DOT_CONFIG_RYT) || $isDoSomeThing;
        $isDoSomeThing = DirHelper::removeFileOrDirInDir(DEvelopmentEnum::DIST) || $isDoSomeThing; // Angular
        $isDoSomeThing = DirHelper::removeFileOrDirInDir(DEvelopmentEnum::COMPOSER_CONFIG_GITHUB_AUTH_FILE) || $isDoSomeThing; // composer config file: auth.json
        //    tmp dir (PHP project)
        if (is_dir(DirHelper::getWorkingDir(DevelopmentEnum::TMP)) || self::inputArr('sub-dir')->count()) {
            DirHelper::tmp('remove', ...self::inputArr('sub-dir'));
            //
            $isDoSomeThing = true;
        }
        //    dangling Docker images / <none> Docker images
        if (DockerHelper::isDockerInstalled()) {
            if (DockerHelper::isDanglingImages()) {
                DockerHelper::removeDanglingImages();
                //
                $isDoSomeThing = true;
            }
        }
        // === end cleanup ===
        // === Slack ===
        if (SlackService::handleInputMessage()) {
            SlackService::sendMessageConsole();
            //
            $isDoSomeThing = true;
        }
        // ===
        if (!$isDoSomeThing) {
            self::LineNew()->print("do nothing");
        }
        self::LineNew()->printSeparatorLine();
    }

    public
    static function clearOpsDir(): void
    {
        self::LineNew()->printTitle("Clear _ops directory");
        (new Process("Clear _ops directory", DirHelper::getWorkingDir(), [
            ShellFactory::generateRemoveFileOrDirCommand(DirHelper::getWorkingDir('_ops'))
        ]))->execMultiInWorkDir(true)->printOutput();
        // validate result
        DirHelper::validateDirOrFileExisting(ValidationTypeEnum::DONT_EXISTS);
        $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '_ops'", DirHelper::getWorkingDir()));
        self::LineNew()->printCondition(!$checkTmpDir, "clear _ops dir successfully", "clear _ops dir failed");
    }
}

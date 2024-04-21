<?php

namespace App\Helpers;

use App\Classes\GitHubRepositoryInfo;
use App\Classes\Release;
use App\Enum\AppInfoEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\PostWorkEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Enum\ValidationTypeEnum;
use App\Classes\Process;
use App\MyOps;
use App\Traits\ConsoleUITrait;

/**
 * This is Ops helper
 */
class OPSHelper
{
    use ConsoleUITrait;

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
        GitHubHelper::handleCachesAndGit([
            'script path',
            'command-name', // param 1
            'myops', // param 2, in this case is repository
            'main', // param 3, in this case is branch
        ]);
        // create an alias 'myops'
        self::createAlias();
        //
        self::LineNew()->printSeparatorLine()
            ->setTag(TagEnum::SUCCESS)->print("sync done");
        self::LineNew()->printSeparatorLine();
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
    private static function createAlias()
    {
        $EngagePlusCachesRepositoryOpsAppReleasePath = sprintf("%s/myops/%s", getenv('ENGAGEPLUS_CACHES_DIR'), AppInfoEnum::RELEASE_PATH);
        $alias = sprintf("alias %s=\"php %s\"", AppInfoEnum::APP_MAIN_COMMAND, $EngagePlusCachesRepositoryOpsAppReleasePath);
        $shellConfigurationFiles = [
            DirHelper::getHomeDir('.zshrc'), // Mac
            DirHelper::getHomeDir('.bashrc'), // Ubuntu
        ];
        foreach ($shellConfigurationFiles as $shellConfigurationFile) {
            if (is_file($shellConfigurationFile)) {
                self::lineNew()->printSubTitle("create alias '%s' at '%s'", AppInfoEnum::APP_MAIN_COMMAND, $shellConfigurationFile);
                // already setup
                if (StrHelper::contains(file_get_contents($shellConfigurationFile), $alias)) {
                    self::lineNew()->setIcon(IconEnum::DOT)->print("already setup alias '%s'", AppInfoEnum::APP_MAIN_COMMAND);
                } else {
                    // setup alias
                    //    remove old alias (wrong path, old date alias)
                    $oldAliases = StrHelper::findLinesContainsTextInFile($shellConfigurationFile, AppInfoEnum::APP_MAIN_COMMAND);
                    foreach ($oldAliases as $oldAlias) {
                        StrHelper::replaceTextInFile([
                            'script path', 'command-name', // param 0,1
                            $oldAlias, '', $shellConfigurationFile
                        ]);
                    }
                    //    add new alias
                    if (file_put_contents($shellConfigurationFile, $alias . PHP_EOL, FILE_APPEND)) {
                        self::lineNew()->setIcon(IconEnum::CHECK)->print("adding alias done");
                    } else {
                        self::lineNew()->setIcon(IconEnum::X)->print("adding alias failure");
                    }
                }
                // validate alias
                self::validate([
                    'script path', 'command-name', // param 0,1
                    ValidationTypeEnum::FILE_CONTAINS_TEXT, "$shellConfigurationFile", $alias
                ]);
            }
        }
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
        self::LineNew()->printTitle("Post works");
        if ($isSkipCheckDir) {
            self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)
                ->print("skip check execution directory");
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
                self::LineNew()->printCondition(!$checkTmpDir,
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
                self::LineNew()->printCondition(!$checkTmpDir,
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
                self::LineNew()->printCondition(!$checkTmpDir,
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
            self::LineNew()->printCondition(!$checkTmpDir,
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
            self::LineNew()->printCondition(!$checkTmpDir,
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
                self::LineNew()->printCondition(
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
            self::LineNew()->print("do nothing");
        }
        self::LineNew()->printSeparatorLine();
    }

    public static function clearOpsDir(): void
    {
        self::LineNew()->printTitle("Clear _ops directory");
        (new Process("Clear _ops directory", DirHelper::getWorkingDir(), [
            sprintf("rm -rf '%s'", DirHelper::getWorkingDir('_ops'))
        ]))->execMultiInWorkDir(true)->printOutput();
        // validate result
        $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '_ops'", DirHelper::getWorkingDir()));
        self::LineNew()->printCondition(!$checkTmpDir, "clear _ops dir successfully", "clear _ops dir failed");
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
            self::LineTagMultiple([TagEnum::ERROR, TagEnum::ENV])->print("missing %s", join(" or ", $envVarsMissing));
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
                self::LineTag(TagEnum::ERROR)->print("invalid action, current support:  %s", join(", ", ValidationTypeEnum::SUPPORT_LIST))
                    ->print("should be like eg:   '%s' validate branch", AppInfoEnum::APP_MAIN_COMMAND);
                break;
        }
    }

    /**
     * allow branches: develop, staging, master
     * should combine with exit 1 in shell:
     *     myops validate branch || exit 1
     * @return void
     */
    private static function validateBranch()
    {
        if (in_array(getenv('BRANCH'), ['develop', 'staging', 'master'])) {
            self::LineTag(TagEnum::SUCCESS)->print("validation branch got OK result: %s", getenv('BRANCH'));
        } else {
            self::LineTag(TagEnum::ERROR)->print("Invalid branch to build | current branch is '%s'", getenv('BRANCH'));
            exit(1); // END app
        }
    }

    /**
     * Docker should is running
     * should combine with exit 1 in shell:
     *      myops validate docker || exit 1
     */
    private static function validateDocker()
    {
        $dockerServer = exec("docker version | grep 'Server:'");
        if (trim($dockerServer)) {
            self::LineTag(TagEnum::SUCCESS)->print("Docker is running: $dockerServer");
        } else {
            self::LineTag(TagEnum::ERROR)->print("Docker isn't running. Please start Docker app.");
            exit(1); // END app
        }
    }

    /**
     * should have env var: BRANCH
     *     myops validate device || exit 1
     * @return void
     */
    private static function validateDevice()
    {
        if (getenv('DEVICE')) {
            self::LineTag(TagEnum::SUCCESS)->print("validation device got OK result: %s", getenv('DEVICE'));
        } else {
            self::LineTag(TagEnum::ERROR)->print("Invalid device | should pass in your command");
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
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])->print("missing filePath or searchText (can path multiple searchText1 searchText2)");
            exit(1);
        }
        if (!is_file($filePath)) {
            self::LineTag(TagEnum::ERROR)->print("'%s' does not exist", $filePath);
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
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::SUCCESS])->print("file '%s' contains text(s): '%s'", $filePath, join("', '", $searchTextArr));
        } else {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->print("file '%s' does not contains (some) text(s):", $filePath);
            foreach ($validationResult as $result) {
                self::LineIndent(IndentLevelEnum::ITEM_LINE)
                    ->setIcon($result['isContains'] ? IconEnum::CHECK : IconEnum::X)
                    ->setColor($result['isContains'] ? UIEnum::COLOR_GREEN : UIEnum::COLOR_RED)
                    ->print($result['searchText']);
            }
            exit(1);
        }
    }
}

<?php

namespace App;

require_once 'app/Helpers/AppHelper.php';
require_once 'app/Traits/ConsoleUITrait.php';
require_once 'app/Helpers/DirHelper.php';

// === class zone ====
use App\Classes\Release;
use App\Classes\Version;
use App\Enum\AppInfoEnum;
use App\Enum\CommandEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Helpers\AppHelper;
use App\Helpers\AWSHelper;
use App\Helpers\Data;
use App\Helpers\DirHelper;
use App\Helpers\DockerHelper;
use App\Helpers\GitHubHelper;
use App\Helpers\OPSHelper;
use App\Helpers\StrHelper;
use App\Services\SlackService;
use App\Traits\ConsoleUITrait;
use DateTime;

AppHelper::requireOneAllPHPFilesInDir(DirHelper::getWorkingDir('app'));

class MyOps
{
    use ConsoleUITrait;

    const SHELL_DATA_BASE_64 = '';

    public static function getShellData()
    {
        return self::SHELL_DATA_BASE_64
            ? base64_decode(self::SHELL_DATA_BASE_64)
            : file_get_contents('app/_shell_/handle-env-ops.sh');
    }

    const ELB_TEMPLATE_BASE_64 = '';

    public static function getELBTemplate()
    {
        return self::ELB_TEMPLATE_BASE_64
            ? json_decode(base64_decode(self::ELB_TEMPLATE_BASE_64), true)
            : [
                'blockdeviceTemplate' => file_get_contents('app/_AWS_/ELB-template/.ebextensions/blockdevice-xvdcz.config.TEMPLATE'),
                'DockerrunTemplate' => file_get_contents('app/_AWS_/ELB-template/Dockerrun.aws.json.TEMPLATE'),
            ];
    }

    public function __construct()
    {

    }

    public function run(array $argv)
    {
        // === params ===
        $command = $argv[1] ?? null;
        $param1 = $argv[2] ?? null; // to use if needed
        $param2 = $argv[3] ?? null; // to use if needed

        // === validation ===
        if (!$command) {
            self::LineTag(TagEnum::ERROR)->print("missing command, should be '%s COMMAND'", AppInfoEnum::APP_MAIN_COMMAND);
            $this->help();
            exit(); // END
        }
        if (!array_key_exists($command, CommandEnum::SUPPORT_COMMANDS())) {
            self::LineTag(TagEnum::ERROR)->print("do not support this command '%s'", $command);
            $this->help();
            exit(); // END
        }

        // === handle ===
        switch ($command) {
            // === this app ===
            case CommandEnum::HELP:
                $this->help();
                break;
            case CommandEnum::RELEASE:
                // release
                (new Release())->handle($argv);
                break;
            case CommandEnum::VERSION:
                // filter color
                if($param1 === 'no-format-color'){
                    self::lineNew()->print(MyOps::getAppVersionStr());
                    break;
                }
                // default
                self::lineColorFormat(UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD)->print(MyOps::getAppVersionStr());
                break;
            case CommandEnum::SYNC:
                OPSHelper::sync();
                break;
            // === AWS related ===
            case CommandEnum::LOAD_ENV_OPS:
                echo AWSHelper::loadOpsEnvAndHandleMore();
                break;
            case CommandEnum::GET_SECRET_ENV:
                // validate
                if (!$param1) {
                    self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                        ->print("required secret name");
                    exit(); // END
                }
                // handle
                AWSHelper::getSecretEnv($param1, $param2);
                break;
            case CommandEnum::ELB_UPDATE_VERSION:
                AWSHelper::ELBUpdateVersion();
                break;
            // === Git / GitHub ===
            case CommandEnum::BRANCH:
                echo exec(GitHubEnum::GET_BRANCH_COMMAND);
                break;
            case CommandEnum::REPOSITORY:
                echo basename(str_replace('.git', '', exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)));
                break;
            case CommandEnum::HEAD_COMMIT_ID:
                echo exec(GitHubEnum::GET_HEAD_COMMIT_ID_COMMAND);
                break;
            case CommandEnum::HANDLE_CACHES_AND_GIT:
                GitHubHelper::handleCachesAndGit($argv);
                break;
            case CommandEnum::FORCE_CHECKOUT:
                GitHubHelper::forceCheckout();
                break;
            //        GitHub Actions
            case CommandEnum::BUILD_ALL_PROJECTS:
                GitHubHelper::buildAllProject();
                break;
            // === Docker ===
            case CommandEnum::DOCKER_KEEP_IMAGE_BY:
                DockerHelper::keepImageBy($argv);
                break;
            case CommandEnum::DOCKER_FILE_ADD_ENVS:
                DockerHelper::DockerfileAddEnvs($argv);
                break;
            // === utils ===
            case CommandEnum::HOME_DIR:
                echo DirHelper::getHomeDir();
                break;
            case  CommandEnum::SCRIPT_DIR:
                echo DirHelper::getScriptDir();
                break;
            case CommandEnum::WORKING_DIR:
                echo DirHelper::getWorkingDir();
                break;
            case CommandEnum::REPLACE_TEXT_IN_FILE:
                StrHelper::replaceTextInFile($argv);
                break;
            case CommandEnum::SLACK:
                SlackService::sendMessage($argv);
                break;
            case CommandEnum::TMP:
                DirHelper::tmp($argv);
                break;
            case CommandEnum::POST_WORK:
                OPSHelper::postWork($argv);
                break;
            case CommandEnum::CLEAR_OPS_DIR:
                OPSHelper::clearOpsDir();
                break;
            // === private ===
            case CommandEnum::GET_S3_WHITE_LIST_IPS_DEVELOPMENT:
                echo OPSHelper::getS3WhiteListIpsDevelopment();
                break;
            case CommandEnum::UPDATE_GITHUB_TOKEN_ALL_PROJECT:
                OPSHelper::updateGitHubTokenAllProjects();
                break;
            // === validation ===
            case CommandEnum::VALIDATE:
                OPSHelper::validate($argv);
                break;
            // === UI/Text ===
            case CommandEnum::TITLE:
                // validate
                if (!$param1) {
                    self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                        ->print("required title text");
                    exit(); // END
                }
                // handle
                self::LineNew()->printTitle($param1);
                break;
            case CommandEnum::SUB_TITLE:
                // validate
                if (!$param1) {
                    self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                        ->print("required sub title text");
                    exit(); // END
                }
                // handle
                self::LineNew()->printSubTitle($param1);
                break;
            // === other ===
            default:
                echo "[ERROR] Unknown error";
                break;
        }
    }

    private function help()
    {
        self::LineNew()->print('')
            ->printTitle("%s v%s", AppInfoEnum::APP_NAME, AppInfoEnum::APP_VERSION)
            ->setTag(TagEnum::INFO)->print("usage:  %s COMMAND", AppInfoEnum::APP_MAIN_COMMAND)
            ->setTag(TagEnum::NONE)->print("               %s COMMAND PARAM1 PARAM2 ...", AppInfoEnum::APP_MAIN_COMMAND)
            ->setTag(TagEnum::NONE)->print('')
            ->setTag(TagEnum::INFO)->print("Support commands:");
        /**
         * @var  $command string
         * @var  $descriptionArr array
         */
        foreach (CommandEnum::SUPPORT_COMMANDS() as $command => $descriptionArr) {
            switch (count($descriptionArr)) {
                case 0: // group command's title
                    self::LineNew()->printSubTitle($command);
                    break;
                case 1: // group command's items - single line description
                    self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)->setIcon(IconEnum::HYPHEN)
                        ->print("%s     : %s", $command, $descriptionArr[0]);
                    break;
                default: // group command's items - multiple line description
                    self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)->setIcon(IconEnum::HYPHEN)->print($command);
                    foreach ($descriptionArr as $descriptionLine) {
                        self::LineIndent(IndentLevelEnum::LEVEL_3)->setIcon(IconEnum::DOT)->print($descriptionLine);
                    }
                    break;
            }
        }
        self::LineNew()->printSeparatorLine();
    }

    public static function getAppVersionStr(Version $newVersion = null): string
    {
        return sprintf("%s v%s", AppInfoEnum::APP_NAME,
            $newVersion ? $newVersion->toString() : AppInfoEnum::APP_VERSION);
    }
}

// === end class zone ====

// === execute zone ===
(new MyOps())->run($argv);
// === end execute zone ===

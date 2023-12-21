<?php

namespace App;


require_once 'App/Helpers/AppHelper.php';
require_once 'App/Helpers/DirHelper.php';

// === class zone ====
use App\Enum\CommandEnum;
use App\Enum\GitHubEnum;
use App\Helpers\AppHelper;
use App\Helpers\AWSHelper;
use App\Helpers\DirHelper;
use App\Helpers\GitHubHelper;
use App\Helpers\OpsHelper;
use App\Helpers\ServicesHelper;
use App\Helpers\TextHelper;
use App\Objects\Release;

AppHelper::requireOneAllPHPFilesInDir('');

class App
{
    const APP_NAME = 'OPS SHARED LIBRARY (PHP)';
    /**
     * 1.0: multiple files PHP and bash scripts
     * 2.0: combine 1 lib file, sync
     * 2.1: with test lib before ship
     * @var string
     */
    const APP_VERSION = '2.0.57';

    const SHELL_HANDLE_ENV_OPS_DATA_BASE64 = '';

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
            echo "[ERROR] missing command, should be 'php _ops/LIB COMMAND'\n";
            $this->help();
            exit(); // END
        }
        if (!array_key_exists($command, CommandEnum::SUPPORT_COMMANDS)) {
            echo sprintf("[ERROR] do not support this command '%s'\n", $command);
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
                (new Release())->handle();
                break;
            case CommandEnum::VERSION:
                TextHelper::message(App::version());
                break;
            case CommandEnum::SYNC:
                OpsHelper::sync(self::SHELL_HANDLE_ENV_OPS_DATA_BASE64);
                break;
            // === AWS related ===
            case CommandEnum::LOAD_ENV_OPS:
                echo AWSHelper::loadOpsEnvAndHandleMore(self::SHELL_HANDLE_ENV_OPS_DATA_BASE64);
                break;
            case CommandEnum::GET_SECRET_ENV:
                // validate
                if (!$param1) {
                    TextHelper::messageERROR("required secret name");
                    exit(); // END
                }
                // handle
                AWSHelper::getSecretEnv($param1, $param2);
                break;
            // === ops ===
            case CommandEnum::BRANCH:
                echo exec(GitHubEnum::GET_BRANCH_COMMAND);
                break;
            case  CommandEnum::REPOSITORY:
                echo basename(str_replace('.git', '', exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)));
                break;
            case CommandEnum::HEAD_COMMIT_ID:
                echo exec(GitHubEnum::GET_HEAD_COMMIT_ID_COMMAND);
                break;
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
                TextHelper::replaceTextInFile($argv);
                break;
            case CommandEnum::HANDLE_CACHES_AND_GIT:
                GitHubHelper::handleCachesAndGit($argv);
                break;
            case CommandEnum::SLACK:
                ServicesHelper::SlackMessage($argv);
                break;
            // === private ===
            case CommandEnum::GET_S3_WHITE_LIST_IPS_DEVELOPMENT:
                echo OpsHelper::getS3WhiteListIpsDevelopment();
                break;
            case CommandEnum::UPDATE_GITHUB_TOKEN_ALL_PROJECT:
                OpsHelper::updateGitHubTokenAllProjects();
                break;
            default:
                echo "[ERROR] Unknown error";
                break;
        }
    }

    private function help()
    {
        TextHelper::message();
        TextHelper::messageTitle(sprintf("%s v%s", self::APP_NAME, self::APP_VERSION));
        echo "[INFO] usage:  php _ops/lib COMMAND  \n";
        echo "               php _ops/lib COMMAND PARAM_1 \n\n";
        echo "[INFO] Support commands:\n";
        foreach (CommandEnum::SUPPORT_COMMANDS as $command => $description) {
            echo sprintf(" +    %s    :  %s\n", $command, $description);
        }
        echo "\n===\n\n";
    }

    public static function version(): string
    {
        return sprintf("%s v%s", self::APP_NAME, self::APP_VERSION);
    }
}

// === end class zone ====

// === execute zone ===
(new App())->run($argv);
// === end execute zone ===

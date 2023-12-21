<?php

namespace App;


require_once 'App/Helpers/AppHelper.php';
require_once 'App/Helpers/DirHelper.php';

// === class zone ====
use App\Enum\CommandEnum;
use App\Helpers\AppHelper;
use App\Helpers\GitHubHelper;
use App\Helpers\OpsHelper;
use App\Helpers\ServicesHelper;
use App\Helpers\TextHelper;
use App\Objects\Release;

AppHelper::requireOneAllPHPFilesInDir('');

class App
{
    public function __construct()
    {

    }

    public function run(array $argv)
    {
        // === params ===
        $command = $argv[1] ?? null;
        $param1 = $argv[2] ?? null; // to use if needed

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
            // === ops ===
            case CommandEnum::BRANCH:
                echo exec("git symbolic-ref HEAD | sed 's/refs\/heads\///g'");
                break;
            case  CommandEnum::REPOSITORY:
                echo basename(str_replace('.git', '', exec('git config --get remote.origin.url')));
                break;
            case CommandEnum::HEAD_COMMIT_ID:
                echo exec("git rev-parse --short HEAD");
                break;
            case CommandEnum::HOME_DIR:
                echo $_SERVER['HOME'];
                break;
            case  CommandEnum::SCRIPT_DIR:
                echo str_replace('/' . basename($_SERVER['SCRIPT_FILENAME']), '', sprintf("%s/%s", $_SERVER['PWD'], $_SERVER['SCRIPT_FILENAME']));
                break;
            case CommandEnum::WORKING_DIR:
                echo $_SERVER['PWD'];
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
                echo OpsHelper::updateGitHubTokenAllProjects();
                break;
            default:
                echo "[ERROR] Unknown error";
                break;
        }
    }

    private function help()
    {
        echo "\n[INFO] usage:  php _ops/lib COMMAND  \n";
        echo "               php _ops/lib COMMAND PARAM_1 \n\n";
        echo "[INFO] Support commands:\n";
        foreach (CommandEnum::SUPPORT_COMMANDS as $command => $description) {
            echo sprintf(" +    %s    :  %s\n", $command, $description);
        }
        echo "\n===\n\n";
    }
}

// === end class zone ====

// === execute zone ===
(new App())->run($argv);
// === end execute zone ===

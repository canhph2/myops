<?php

namespace App\Enum;

class CommandEnum
{
    // === this app commands ===
    const HELP = 'help';
    const RELEASE = 'release';
    const VERSION = 'version';
    // === SHELL DATA commands
    const LOAD_ENV_OPS = 'load-env-ops';
    const GET_SECRET_ENV = 'get-secret-env';
    // === ops commands ===
    const BRANCH = 'branch';
    const REPOSITORY = 'repository';
    const HEAD_COMMIT_ID = 'head-commit-id';
    const HOME_DIR = 'home-dir';
    const SCRIPT_DIR = 'script-dir';
    const WORKING_DIR = 'working-dir';
    const REPLACE_TEXT_IN_FILE = 'replace-text-in-file';
    const HANDLE_CACHES_AND_GIT = 'handle-caches-and-git';
    const SLACK = 'slack';
    // === ops private commands ===
    const GET_S3_WHITE_LIST_IPS_DEVELOPMENT = 'get-s3-white-list-ips-develop';
    const UPDATE_GITHUB_TOKEN_ALL_PROJECT = 'update-github-token-all-project';


    /**
     * @var array
     * key => value | key is command, value is description
     */
    const SUPPORT_COMMANDS = [
        self::HELP => 'show list support command and usage',
        self::RELEASE => 'combine all PHP files into \'_ops/lib\'',
        self::VERSION => "show app version\n",
        // AWS releated commands
        self::LOAD_ENV_OPS => "[AWS Secret Manager] [CREDENTIAL REQUIRED] load env ops, usage in Shell:\n\n                         eval \"$(php _ops/lib load-env-ops)\"    \n",
        self::GET_SECRET_ENV => "[AWS Secret Manager] [CREDENTIAL REQUIRED] get .env | params:  secretName, customENVName\n",
        //
        self::BRANCH => 'get git branch / GitHub branch',
        self::REPOSITORY => 'get GitHub repository name',
        self::HEAD_COMMIT_ID => 'get head commit id of branch',
        self::HOME_DIR => 'return home directory of machine / server',
        self::SCRIPT_DIR => 'return directory of script',
        self::WORKING_DIR => 'get root project directory / current working directory',
        self::REPLACE_TEXT_IN_FILE => 'php _ops/lib replace-text-in-file "search text" "replace text" "file path"',
        self::HANDLE_CACHES_AND_GIT => 'handle GitHub repository in caches directory',
        self::SLACK => "notify a message to Slack\n",
        // private
        self::GET_S3_WHITE_LIST_IPS_DEVELOPMENT => '[PRIVATE] get S3 whitelist IPs to add to AWS Policy',
        self::UPDATE_GITHUB_TOKEN_ALL_PROJECT => '[PRIVATE] update token all projects in workspace',
    ];
}

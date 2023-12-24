<?php

namespace app\Enum;

class CommandEnum
{
    // === this app commands ===
    const HELP = 'help';
    const RELEASE = 'release';
    const VERSION = 'version';
    const SYNC = 'sync';

    // === AWS related DATA commands
    const LOAD_ENV_OPS = 'load-env-ops';
    const GET_SECRET_ENV = 'get-secret-env';
    const ELB_UPDATE_VERSION = 'elb-update-version';

    // === git ===
    const BRANCH = 'branch';
    const REPOSITORY = 'repository';
    const HEAD_COMMIT_ID = 'head-commit-id';
    const HANDLE_CACHES_AND_GIT = 'handle-caches-and-git';

    // === utils ===
    const HOME_DIR = 'home-dir';
    const SCRIPT_DIR = 'script-dir';
    const WORKING_DIR = 'working-dir';
    const REPLACE_TEXT_IN_FILE = 'replace-text-in-file';
    const SLACK = 'slack';
    const TMP = 'tmp';
    const POST_WORK = 'post-work';

    // === ops private commands ===
    const GET_S3_WHITE_LIST_IPS_DEVELOPMENT = 'get-s3-white-list-ips-develop';
    const UPDATE_GITHUB_TOKEN_ALL_PROJECT = 'update-github-token-all-project';

    // === validation ===
    const VALIDATE = 'validate';

    /**
     * @var array
     * key => value | key is command, value is description
     */
    const SUPPORT_COMMANDS = [
        self::HELP => 'show list support command and usage',
        self::RELEASE => "combine all PHP files into '_ops/lib'
                        default version increasing is 'patch'
                        feature should be 'minor'",
        self::VERSION => "show app version",
        self::SYNC => "sync new release code to project at _ops/lib",

        "=== AWS related commands ===" => '',
        self::LOAD_ENV_OPS => "[AWS Secret Manager] [CREDENTIAL REQUIRED] load env ops, usage in Shell:
                               eval \"$(php _ops/lib load-env-ops)\"   \n",
        self::GET_SECRET_ENV => "[AWS Secret Manager] [CREDENTIAL REQUIRED] get .env | params:  secretName, customENVName",
        self::ELB_UPDATE_VERSION => "[AWS Elastic Beanstalk] create a new version and update an environment",

        "=== git ===" => '',
        self::BRANCH => 'get git branch / GitHub branch',
        self::REPOSITORY => 'get GitHub repository name',
        self::HEAD_COMMIT_ID => 'get head commit id of branch',
        self::HANDLE_CACHES_AND_GIT => 'handle GitHub repository in caches directory',

        "=== utils ===" => '',
        self::HOME_DIR => 'return home directory of machine / server',
        self::SCRIPT_DIR => 'return directory of script',
        self::WORKING_DIR => 'get root project directory / current working directory',
        self::REPLACE_TEXT_IN_FILE => 'php _ops/lib replace-text-in-file "search text" "replace text" "file path"',
        self::SLACK => "notify a message to Slack",
        self::TMP => "handle temporary directory (tmp), use 'tmp add' to add new tmp dir, use 'tmp remove' to remove tmp dir",
        self::POST_WORK => "do post works, eg cleanup ...",

        "=== private ===" => '',
        self::GET_S3_WHITE_LIST_IPS_DEVELOPMENT => '[PRIVATE] get S3 whitelist IPs to add to AWS Policy',
        self::UPDATE_GITHUB_TOKEN_ALL_PROJECT => '[PRIVATE] update token all projects in workspace',

        "=== validation ===" => '',
        self::VALIDATE => "required: 'set -e' in bash file
                    should combine with exit 1, eg:   php _ops/lib validate TYPE | exit 1
                    supports:
                    - branch  : to only allow develop, staging, master
                    - docker  : docker should is running
                    - device: should pass env var: DEVICE in your first command
                    ",
    ];
}

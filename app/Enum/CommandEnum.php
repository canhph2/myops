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

    // === Git/GitHub ===
    const BRANCH = 'branch';
    const REPOSITORY = 'repository';
    const HEAD_COMMIT_ID = 'head-commit-id';
    const HANDLE_CACHES_AND_GIT = 'handle-caches-and-git';
    const FORCE_CHECKOUT = 'force-checkout';
    //        GitHub Actions
    const BUILD_ALL_PROJECTS = 'build-all-projects';

    // === Docker ===
    const DOCKER_KEEP_IMAGE_BY = 'docker-keep-image-by';
    const DOCKER_FILE_ADD_ENVS = 'dockerfile-add-envs';

    // === utils ===
    const HOME_DIR = 'home-dir';
    const SCRIPT_DIR = 'script-dir';
    const WORKING_DIR = 'working-dir';
    const REPLACE_TEXT_IN_FILE = 'replace-text-in-file';
    const SLACK = 'slack';
    const TMP = 'tmp';
    const POST_WORK = 'post-work';
    const CLEAR_OPS_DIR = 'clear-ops-dir';

    // === ops private commands ===
    const GET_S3_WHITE_LIST_IPS_DEVELOPMENT = 'get-s3-white-list-ips-develop';
    const UPDATE_GITHUB_TOKEN_ALL_PROJECT = 'update-github-token-all-project';

    // === validation ===
    const VALIDATE = 'validate';

    // === UI/Text ===
    const TITLE = 'title';
    const SUB_TITLE = 'sub-title';

    /**
     * @var array
     * key => value | key is command, value is description
     */
    const SUPPORT_COMMANDS = [
        // group title
        "OPS APP" => [],
        self::HELP => ['show list support command and usage'],
        self::RELEASE => [
            "combine all PHP files into '_ops/lib'",
            "default version increasing is 'patch'",
            "feature should be 'minor'",
        ],
        self::VERSION => ["show app version"],
        self::SYNC => ["sync new release code to project at _ops/lib"],
        // group title
        "AWS Related" => [],
        self::LOAD_ENV_OPS => [
            '[AWS Secret Manager] [CREDENTIAL REQUIRED] load env ops, usage in Shell:',
            '            eval "$(php _ops/lib load-env-ops)"    ',
        ],
        self::GET_SECRET_ENV => ["[AWS Secret Manager] [CREDENTIAL REQUIRED] get .env | params:  secretName, customENVName"],
        self::ELB_UPDATE_VERSION => ["[AWS Elastic Beanstalk] create a new version and update an environment"],
        // group title
        "GIT / GITHUB" => [],
        self::BRANCH => ['get git branch / GitHub branch'],
        self::REPOSITORY => ['get GitHub repository name'],
        self::HEAD_COMMIT_ID => ['get head commit id of branch'],
        self::HANDLE_CACHES_AND_GIT => ['handle GitHub repository in caches directory'],
        self::FORCE_CHECKOUT => [
            'force checkout a GitHub repository with specific branch',
            '.e.g to test source code in the server'
        ],
        //        GitHub Actions
        self::BUILD_ALL_PROJECTS => ['[GitHub Actions] build all projects to keep the GitHub runner token connecting'], // ES-2381
        // group title
        "DOCKER" => [],
        self::DOCKER_KEEP_IMAGE_BY => ['Keep image by repository and tag, use for keep latest image. Required:  imageRepository imageTag'],
        self::DOCKER_FILE_ADD_ENVS => ['add ENVs into Dockerfile below FROM line. Required: DockerfilePath, secretName'],
        // group title
        "UTILS" => [],
        self::HOME_DIR => ['return home directory of machine / server'],
        self::SCRIPT_DIR => ['return directory of script'],
        self::WORKING_DIR => ['get root project directory / current working directory'],
        self::REPLACE_TEXT_IN_FILE => ['php _ops/lib replace-text-in-file "search text" "replace text" "file path"'],
        self::SLACK => ["notify a message to Slack"],
        self::TMP => [
            'handle temporary directory (tmp)',
            "use 'tmp add' to add new tmp dir",
            "use 'tmp remove' to remove tmp dir"
        ],
        self::POST_WORK => ["do post works. Optional: add param 'skip-check-dir' to skip check dir"],
        self::CLEAR_OPS_DIR => ["clear _ops directory, usually use in Docker image"],
        // group title
        "PRIVATE" => [],
        self::GET_S3_WHITE_LIST_IPS_DEVELOPMENT => ['[PRIVATE] get S3 whitelist IPs to add to AWS Policy'],
        self::UPDATE_GITHUB_TOKEN_ALL_PROJECT => ['[PRIVATE] update token all projects in workspace'],
        // group title
        "VALIDATION" => [],
        self::VALIDATE => [
            "required: 'set -e' in bash file",
            '  should combine with exit 1, eg:   php _ops/lib validate TYPE | exit 1',
            '  support TYPEs:',
            '    branch  : to only allow develop, staging, master',
            '    docker  : docker should is running',
            '    device  : should pass env var: DEVICE in your first command',
            '    file-contains-text  : check if file should contain a text or some texts',
        ],
        // group title
        "UI/Text" => [],
        self::TITLE => ["print a title in terminal/console"],
        self::SUB_TITLE => ["print a sub title in terminal/console"],
    ];
}

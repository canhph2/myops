<?php

namespace App\Enum;

class CommandEnum
{
    // === this app commands ===
    const HELP = 'help';
    const RELEASE = 'release';
    const VERSION = 'version';
    const SYNC = 'sync';
    const CREATE_ALIAS_DIRECTLY = 'create-alias-directly';

    // === AWS related DATA commands
    const LOAD_ENV_OPS = 'load-env-ops';
    const GET_SECRET_ENV = 'get-secret-env';
    const ELB_UPDATE_VERSION = 'elb-update-version';
    const AUTOMATE_TO_SWITCH_AWS_CREDENTIAL_FOR_CICD = 'automate-switching-aws-credential-for-cicd';

    // === Git/GitHub ===
    const BRANCH = 'branch';
    const REPOSITORY = 'repository';
    const HEAD_COMMIT_ID = 'head-commit-id';
    const CHECKOUT_CACHES = 'checkout-caches';
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
    const PRE_WORK = 'pre-work';
    const POST_WORK = 'post-work';
    const CLEAR_OPS_DIR = 'clear-ops-dir';
    const TIME = 'time';
    const PROCESS = 'process';

    // === ops private commands ===
    const GET_S3_WHITE_LIST_IPS_DEVELOPMENT = 'get-s3-white-list-ips-develop';
    const UPDATE_GITHUB_TOKEN_ALL_PROJECT = 'update-github-token-all-project';

    // === validation ===
    const VALIDATE = 'validate';

    // === UI/Text ===
    const TITLE = 'title';
    const SUB_TITLE = 'sub-title';

    // === others ==
    const ON_REQUIRE_FILE = 'ON_REQUIRE_FILE';

    /**
     * @return array
     * key => value | key is command, value is description
     */
    public static function SUPPORT_COMMANDS(): array
    {
        return [
            // group title
            AppInfoEnum::APP_NAME => [],
            'Required notes:' => [
                '[Alias required] add these commands below in a beginning of your shell script file:',
                "        # [Alias required] load shell configuration",
                "        [[ -f ~/.zshrc ]] && source ~/.zshrc # MAC",
                "        [[ -f ~/.bashrc ]] && source ~/.bashrc # Ubuntu",
            ],
            self::HELP => [
                'show list support command and usage',
                sprintf("add arg1 is your <search command> to highlight red the command .e.g % help searchA", AppInfoEnum::APP_MAIN_COMMAND),
            ],
            self::RELEASE => [
                sprintf("combine all PHP files into '.release/MyOps.php' and install a alias '%s'", AppInfoEnum::APP_MAIN_COMMAND),
                "default version increasing is 'patch'",
                "feature should be 'minor'",
            ],
            self::VERSION => ["show app version, (without format and color, using option 'no-format-color'"],
            self::SYNC => [sprintf("sync new release code to caches dir and create an alias '%s'", AppInfoEnum::APP_MAIN_COMMAND)],
            self::CREATE_ALIAS_DIRECTLY => ['create an alias directly to the MyOps.php call this command, use to manually install'],
            // group title
            "AWS Related" => [],
            self::LOAD_ENV_OPS => [
                '[AWS Secret Manager] [CREDENTIAL REQUIRED] load env ops, usage in Shell:',
                sprintf('    eval "$(%s load-env-ops)"    ', AppInfoEnum::APP_MAIN_COMMAND),
                "[MOVED] to " . self::PRE_WORK
            ],
            self::GET_SECRET_ENV => ["[AWS Secret Manager] [CREDENTIAL REQUIRED] get .env | params:  secretName, customENVName"],
            self::ELB_UPDATE_VERSION => ["[AWS Elastic Beanstalk] create a new version and update an environment"],
            self::AUTOMATE_TO_SWITCH_AWS_CREDENTIAL_FOR_CICD => ['Automate to switch AWS Credential Keys for deployments in GitHub Runner Server'],
            // group title
            "GIT / GITHUB" => [],
            self::BRANCH => ['get git branch / GitHub branch'],
            self::REPOSITORY => ['get GitHub repository name'],
            self::HEAD_COMMIT_ID => ['get head commit id of branch'],
            self::CHECKOUT_CACHES => ['checkout GitHub repository in caches directory'],
            self::FORCE_CHECKOUT => [
                'force checkout a GitHub repository with specific branch',
                '.e.g to test source code in the server'
            ],
            //        GitHub Actions
            self::BUILD_ALL_PROJECTS => [
                '[GitHub Actions] build all projects to keep the GitHub runner token connecting',
                "require input the 'workspace directory' .e.g 'caches directory' or 'develop workspace directory' "
            ], // ES-2381
            // group title
            "DOCKER" => [],
            self::DOCKER_KEEP_IMAGE_BY => ['Keep image by repository and tag, use for keep latest image. Required:  imageRepository imageTag'],
            self::DOCKER_FILE_ADD_ENVS => ['add ENVs into Dockerfile below FROM line. Required: DockerfilePath, secretName'],
            // group title
            "UTILS" => [],
            self::HOME_DIR => ['return home directory of machine / server'],
            self::SCRIPT_DIR => ['return directory of script'],
            self::WORKING_DIR => ['get root project directory / current working directory'],
            self::REPLACE_TEXT_IN_FILE => [sprintf('php %s replace-text-in-file "search text" "replace text" "file path"', AppInfoEnum::APP_MAIN_COMMAND)],
            self::SLACK => [
                "notify a message to Slack",
                "use --message=<custom message> to send a custom message",
                "use --type=start or --type=finish to send a message of process",
                "use --process-id=<PROCESS_ID> to handle process time",
                "use --indent=A to indent the message with |-      <your message>",
            ],
            self::TMP => [
                'handle temporary directory (tmp)',
                "use the sub-command 'add' to add new tmp dir",
                "use the sub-command 'remove' to remove tmp dir",
                "user the option --sub-dir=<sub-dir 1> --sub-dir=<sub-dir 2> to handle temp dir in the sub-dir"
            ],
            self::PRE_WORK => [
                '[AWS Secret Manager] [CREDENTIAL REQUIRED] to load env ops, usage in Shell:',
                sprintf('    eval "$(%s pre-work --response-type=eval)"    ', AppInfoEnum::APP_MAIN_COMMAND),
                "Handle:",
                '  - [EVAL] load env ops',
                '  - [EVAL] create a MyOps process, will export PROCESS_ID to the env',
                '  - [NORMAL] show version',
                '  - [NORMAL] automate to switch AWS credential keys',
                '  - [NORMAL] slack notify to start, will support Slack options above',
            ],
            self::POST_WORK => [
                "do post works",
                "[Optional] add param '--skip-check-dir=1' to skip check dir",
                "[Optional] add param '--process-id=<PROCESS_ID>' to get process build time",
                '[NORMAL] slack notify to finish, will support Slack options above',
            ],
            self::CLEAR_OPS_DIR => ["clear .ops directory, usually use in Docker image"],
            self::TIME => [
                'is used to measure project build time',
                "use the sub-command 'begin' to mark a beginning time, will return an id of process object",
                "use the sub-command 'end' to mark an ending time, will return a text of period time",
            ],
            self::PROCESS => [
                "is used to handle 'MyOps process', the first version just show some info and mark starting time",
                "use the sub-command 'start' to handle starting a 'MyOps process', wil return a process id "
            ],
            // group title
            "PRIVATE" => [],
            self::GET_S3_WHITE_LIST_IPS_DEVELOPMENT => ['[PRIVATE] get S3 whitelist IPs to add to AWS Policy'],
            self::UPDATE_GITHUB_TOKEN_ALL_PROJECT => ['[PRIVATE] update token all projects in workspace'],
            // group title
            "VALIDATION" => [],
            self::VALIDATE => [ // set -e # tells the shell to exit if a command returns a non-zero exit status
                "required: 'set -e' in bash file",
                '  [NEW] batch validation: --type=<type1> --type=<type2>...',
                '  support TYPEs:',
                '    branch  : to only allow develop, staging, master',
                '    docker  : docker should is running',
                '    device  : should pass env var: DEVICE in your first command',
                '    file-contains-text  : check if a file should contain a text or some texts',
                '    exists DIR FILE_OR_DIR_1 FILE_OR_DIR_1 ... : check if a file or a directory should exists in a directory',
                '    dont-exists DIR FILE_OR_DIR_1 FILE_OR_DIR_1 ... : check if a file or a directory should exists in a directory',
            ],
            // group title
            "UI/Text" => [],
            self::TITLE => ["print a title in terminal/console"],
            self::SUB_TITLE => ["print a sub title in terminal/console"],
        ];
    }
}

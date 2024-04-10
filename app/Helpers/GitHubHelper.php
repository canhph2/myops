<?php

namespace app\Helpers;

use app\Classes\GitHubRepositoryInfo;
use app\Classes\Process;
use app\Enum\CommandEnum;
use app\Enum\GitHubEnum;
use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\TagEnum;
use app\Services\SlackService;

/**
 * This is a GitHub helper
 */
class GitHubHelper
{
    /**
     * get current GitHub info, will return
     * [REMOTE_ORIGIN_URL, GITHUB_PERSONAL_TOKEN, USERNAME, REPOSITORY_NAME]
     *
     * @param string|null $remoteOriginUrl
     * @return array
     */
    public static function parseGitHub(string $remoteOriginUrl = null): array
    {
        $remoteOriginUrl = $remoteOriginUrl ?? self::getRemoteOriginUrl_Current();
        return [
            $remoteOriginUrl,
            strpos($remoteOriginUrl, "@") !== false
                ? str_replace('https://', '', explode('@', $remoteOriginUrl)[0])
                : null,
            basename(str_replace(basename($remoteOriginUrl), '', $remoteOriginUrl)),
            basename(str_replace('.git', '', $remoteOriginUrl))
        ];
    }

    public static function getRemoteOriginUrl_Current(): ?string
    {
        return exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND);
    }

    /**
     * @param string $repository
     * @param string|null $GitHubPersonalAccessToken
     * @return string
     */
    public static function getRemoteOriginUrl_Custom(string $repository, string $GitHubPersonalAccessToken = null): string
    {
        return $GitHubPersonalAccessToken
            ? sprintf("https://%s@github.com/%s/%s.git", $GitHubPersonalAccessToken, GitHubEnum::GITHUB_REPOSITORIES[$repository], $repository)
            : sprintf("https://github.com/%s/%s.git", GitHubEnum::GITHUB_REPOSITORIES[$repository], $repository);
    }

    public static function setRemoteOriginUrl(string $remoteOriginUrl, string $workingDir = null, bool $isCheckResult = false): void
    {
        $commandsToCheckResult = $isCheckResult ? [GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND] : [];
        (new Process(
            "GitHub Set Remote Origin Url",
            $workingDir ?? DirHelper::getWorkingDir(),
            array_merge([
                sprintf("git remote set-url origin %s", $remoteOriginUrl)
            ], $commandsToCheckResult)
        ))->execMultiInWorkDir()->printOutput();
    }

    public static function getBranchUsingCommand(string $workDir): ?string
    {
        $process = (new Process(__FUNCTION__, $workDir, [
            GitHubEnum::GET_BRANCH_COMMAND
        ]))->execMultiInWorkDir()
            ->printOutput();
        return $process->getOutput() ? $process->getOutput()[count($process->getOutput()) - 1] : null;
    }

    /**
     * checking git already exist in this directory / folder
     * @param string $dirToCheck
     * @return bool
     */
    public static function isGit(string $dirToCheck): bool
    {
        return is_dir(sprintf("%s/.git", $dirToCheck));
    }

    public static function getRepositoryDirCommand(): string
    {
        return exec(GitHubEnum::GET_REPOSITORY_DIR_COMMAND);
    }

    /**
     * usage:
     *     php _ops/lib handle-caches-and-git REPOSITORY
     *
     * required:
     *     ENV > GITHUB_PERSONAL_ACCESS_TOKEN
     *
     * case engage-api-deploy, to build api docker image
     *     php _ops/lib_temp/HandleCachesAndGit ENGAGE_API_DEPLOY
     */
    public static function handleCachesAndGit(array $argv)
    {
        // === param ===
        $param2 = $argv[2] ?? null;
        $param3 = $argv[3] ?? null;
        // === validate ===
        //    validate env vars
        $repository = $param2 ?? getenv('REPOSITORY');
        $branch = $param3 ?? getenv('BRANCH');
        if ($repository === 'engage-api-deploy') {
            $branch = $param3 ?? getenv('API_DEPLOY_BRANCH');
        }
        $engagePlusCachesDir = getenv('ENGAGEPLUS_CACHES_DIR');
        $GitHubPersonalAccessToken = getenv('GITHUB_PERSONAL_ACCESS_TOKEN');

        if (!$repository || !$branch || !$engagePlusCachesDir || !$GitHubPersonalAccessToken) {
            TextHelper::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->message("missing a REPOSITORY or BRANCH or ENGAGEPLUS_CACHES_DIR or GITHUB_PERSONAL_ACCESS_TOKEN");
            exit(); // END
        }

        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", $engagePlusCachesDir, $repository);
        //     message validate
        TextHelper::tag($param2 ? 'CUSTOM' : 'ENV')->message("REPOSITORY = %s", $repository)
            ->setTag($param3 ? 'CUSTOM' : 'ENV')->message("BRANCH = %s", $branch)
            ->message("DIR = '$EngagePlusCachesRepositoryDir'");

        // === handle ===
        TextHelper::tag(TagEnum::GIT)->messageTitle("Handle Caches and Git");
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            TextHelper::new()->message("The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository");
            //
            // case clone
        } else {
            TextHelper::tag(TagEnum::ERROR)->message("The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository");
            //
            (new Process("Remove old directory", null, [
                sprintf("rm -rf \"%s\"", $EngagePlusCachesRepositoryDir),
                sprintf("mkdir -p \"%s\"", $EngagePlusCachesRepositoryDir),
            ]))->execMulti()->printOutput();
            //
            (new Process("CLONE SOURCE CODE", $EngagePlusCachesRepositoryDir, [
                sprintf("git clone -b %s %s .", $branch, self::getRemoteOriginUrl_Custom($repository, $GitHubPersonalAccessToken)),
            ]))->execMultiInWorkDir(true)->printOutput();
        }
        // === update new code ===
        (new Process("UPDATE SOURCE CODE", $EngagePlusCachesRepositoryDir, [
            sprintf("git remote set-url origin %s", self::getRemoteOriginUrl_Custom($repository, $GitHubPersonalAccessToken)),
            GitHubEnum::RESET_BRANCH_COMMAND,
            sprintf("git checkout %s", $branch),
            GitHubEnum::PULL_COMMAND
        ]))->execMultiInWorkDir()->printOutput();
        // === remove token ===
        self::setRemoteOriginUrl(self::getRemoteOriginUrl_Custom($repository), $EngagePlusCachesRepositoryDir, true);
    }

    public static function forceCheckout()
    {
        TextHelper::new()->messageTitle("Force checkout a GitHub repository with specific branch");
        // === input ===
        $GIT_URL_WITH_TOKEN = readline("Please input GIT_URL_WITH_TOKEN? ");
        if (!$GIT_URL_WITH_TOKEN) {
            TextHelper::tag(TagEnum::ERROR)->message("GitHub repository url with Token should be string");
            exit(); // END
        }
        $BRANCH_TO_FORCE_CHECKOUT = readline("Please input BRANCH_TO_FORCE_CHECKOUT? ");
        if (!$BRANCH_TO_FORCE_CHECKOUT) {
            TextHelper::tag(TagEnum::ERROR)->message("branch to force checkout should be string");
            exit(); // END
        }
        // === validation ===
        if (!(StrHelper::contains($GIT_URL_WITH_TOKEN, 'https://')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '@github.com')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '.git')
        )) {
            TextHelper::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::FORMAT])
                ->message("invalid GitHub repository url with Token format, should be 'https://TOKEN_TOKEN@@github.com/USER_NAME/REPOSITORY.git'");
            exit(); // END
        }
        // === handle ===
        $initGitCommands = self::isGit(DirHelper::getWorkingDir()) ? [] : [GitHubEnum::INIT_REPOSITORY_COMMAND];
        $setRemoteOriginUrlCommand = exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)
            ? sprintf("git remote set-url origin %s", $GIT_URL_WITH_TOKEN)
            : sprintf("git remote add origin %s", $GIT_URL_WITH_TOKEN);
        (new Process("Set repository remote url and force checkout branch", DirHelper::getWorkingDir(), array_merge($initGitCommands, [
            $setRemoteOriginUrlCommand,
            GitHubEnum::PULL_COMMAND,
            GitHubEnum::RESET_BRANCH_COMMAND,
            sprintf("git checkout -f %s", $BRANCH_TO_FORCE_CHECKOUT),
            GitHubEnum::PULL_COMMAND,
        ])))->execMultiInWorkDir(true)->printOutput();
        // === validate result ===
        (new Process("Validate branch", DirHelper::getWorkingDir(), [
            GitHubEnum::GET_BRANCH_COMMAND
        ]))->execMultiInWorkDir()->printOutput();
    }

    /**
     * [GitHub Actions]
     * build strategy:
     *  #1 20 minutes per project, 8 projects x 20 ~ 3 hours per command : not good | run weekly
     *  #2 run every 3 minutes, 1 project, rebuild after 7 days | run every 30 minutes | need saved data
     * steps:
     *    1. get token from Secret (require aws credential)
     *    2. login gh with token
     *    3. run workflow
     * @return void
     */
    public static function buildAllProject()
    {
        $branchToBuild = GitHubEnum::DEVELOP;
        TextHelper::new()->messageTitle("Build all projects to keep the GitHub runner token connecting (develop env)");
        // validate
        $GitHubToken = AWSHelper::getValueEnvOpsSecretManager('GITHUB_PERSONAL_ACCESS_TOKEN');
        if (!$GitHubToken) {
            TextHelper::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->message("GitHub token not found (in Secret Manager)");
            return; //END
        }
        // handle
        //    notify
        SlackService::sendMessageInternalUsing(sprintf("[BEGIN] %s", CommandEnum::SUPPORT_COMMANDS[CommandEnum::BUILD_ALL_PROJECTS][0]), DirHelper::getProjectDirName(), $branchToBuild);
        //    get GitHub token and login gh
        TextHelper::new()->messageSubTitle("login gh (GitHub CLI)");
        (new Process("login gh (GitHub CLI)", DirHelper::getWorkingDir(), [
            sprintf("echo %s | gh auth login --with-token", $GitHubToken),
        ]))->execMultiInWorkDir();
        //    send command to build all projects
        TextHelper::new()->messageSubTitle("send command to build all projects");
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        TextHelper::new()->message("WORKSPACE DIR = $workspaceDir");
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            $projectDir = sprintf("%s/%s", $workspaceDir, $repoInfo->getRepositoryName());
            if (is_dir($projectDir)) {
                // show info
                TextHelper::icon(IconEnum::PLUS)->message("Project '%s > %s'(%s): %s",
                    $repoInfo->getUsername(), $repoInfo->getRepositoryName(), $branchToBuild,
                    $repoInfo->isGitHubAction() ? "✔" : "X"
                );
                // handle send command to build
                if ($repoInfo->isGitHubAction()) {
                    (new Process("build project " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
                        sprintf("cd '%s'", $projectDir),
                        sprintf('gh workflow run workflow--%s--%s -r %s', $repoInfo->getRepositoryName(), $branchToBuild, $branchToBuild)
                    ]))->execMultiInWorkDir();
                    // wait to send next command
                    TextHelper::icon(IconEnum::DOT)->setIndentLevel(IndentLevelEnum::ITEM_LINE)
                        ->message("wait %s minute...", $repoInfo->getAmountBuildTimeInMinute());
                    sleep($repoInfo->getAmountBuildTimeInSecond());
                }
            }
        } // end loop
        //    notify
        SlackService::sendMessageInternalUsing(sprintf("[END] %s", CommandEnum::SUPPORT_COMMANDS[CommandEnum::BUILD_ALL_PROJECTS][0]), DirHelper::getProjectDirName(), $branchToBuild);
    }
}

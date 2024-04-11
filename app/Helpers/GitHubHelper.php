<?php

namespace App\Helpers;

use App\Classes\Duration;
use App\Classes\GitHubRepositoryInfo;
use App\Classes\Process;
use App\Enum\CommandEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Services\SlackService;
use App\Traits\ConsoleUITrait;
use DateTime;

/**
 * This is a GitHub helper
 */
class GitHubHelper
{
    use ConsoleUITrait;

    /**
     * @param string $name
     * @return false|GitHubRepositoryInfo|null
     */
    public static function getRepositoryInfoByName(string $name)
    {
        $repoArr = array_filter(GitHubEnum::GET_REPOSITORIES_INFO(), function ($repository) use ($name) {
            /** @var GitHubRepositoryInfo $repository */
            return $repository->getRepositoryName() === $name;
        });
        return reset($repoArr);
    }

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
     * @param string $repositoryName
     * @param string|null $GitHubPersonalAccessToken
     * @return string
     */
    public static function getRemoteOriginUrl_Custom(string $repositoryName, string $GitHubPersonalAccessToken = null): string
    {
        return $GitHubPersonalAccessToken
            ? sprintf("https://%s@github.com/%s/%s.git", $GitHubPersonalAccessToken, self::getRepositoryInfoByName($repositoryName)->getUsername(), $repositoryName)
            : sprintf("https://github.com/%s/%s.git", self::getRepositoryInfoByName($repositoryName)->getUsername(), $repositoryName);
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
     * require envs: GITHUB_PERSONAL_ACCESS_TOKEN
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
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->message("missing a REPOSITORY or BRANCH or ENGAGEPLUS_CACHES_DIR or GITHUB_PERSONAL_ACCESS_TOKEN");
            exit(); // END
        }

        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", $engagePlusCachesDir, $repository);
        //     message validate
        self::LineTag($param2 ? 'CUSTOM' : 'ENV')->message("REPOSITORY = %s", $repository)
            ->setTag($param3 ? 'CUSTOM' : 'ENV')->message("BRANCH = %s", $branch)
            ->message("DIR = '$EngagePlusCachesRepositoryDir'");

        // === handle ===
        self::LineTag(TagEnum::GIT)->messageTitle("Handle Caches and Git");
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            self::LineNew()->message("The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository");
            //
            // case clone
        } else {
            self::LineTag(TagEnum::ERROR)->message("The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository");
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
        self::LineNew()->messageTitle("Force checkout a GitHub repository with specific branch");
        // === input ===
        $GIT_URL_WITH_TOKEN = readline("Please input GIT_URL_WITH_TOKEN? ");
        if (!$GIT_URL_WITH_TOKEN) {
            self::LineTag(TagEnum::ERROR)->message("GitHub repository url with Token should be string");
            exit(); // END
        }
        $BRANCH_TO_FORCE_CHECKOUT = readline("Please input BRANCH_TO_FORCE_CHECKOUT? ");
        if (!$BRANCH_TO_FORCE_CHECKOUT) {
            self::LineTag(TagEnum::ERROR)->message("branch to force checkout should be string");
            exit(); // END
        }
        // === validation ===
        if (!(StrHelper::contains($GIT_URL_WITH_TOKEN, 'https://')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '@github.com')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '.git')
        )) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::FORMAT])
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
        self::LineNew()->messageTitle("Build all projects to keep the GitHub runner token connecting (develop env)");
        // validate
        $GitHubToken = AWSHelper::getValueEnvOpsSecretManager('GITHUB_PERSONAL_ACCESS_TOKEN');
        if (!$GitHubToken) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->message("GitHub token not found (in Secret Manager)");
            return; //END
        }
        // handle
        //    notify
        SlackService::sendMessageInternalUsing(sprintf("[BEGIN] %s", CommandEnum::SUPPORT_COMMANDS()[CommandEnum::BUILD_ALL_PROJECTS][0]), DirHelper::getProjectDirName(), $branchToBuild);
        //    get GitHub token and login gh
        self::LineNew()->messageSubTitle("login gh (GitHub CLI)");
        (new Process("login gh (GitHub CLI)", DirHelper::getWorkingDir(), [
            sprintf("echo %s | gh auth login --with-token", $GitHubToken),
        ]))->execMultiInWorkDir();
        //    send command to build all projects
        self::LineNew()->messageSubTitle("send command to build all projects");
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        self::LineNew()->message("WORKSPACE DIR = $workspaceDir");
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            $repoInfo->setCurrentWorkspaceDir($workspaceDir)->setCurrentBranch($branchToBuild);
            if (is_dir($repoInfo->getCurrentRepositoryDir())) {
                // show info
                self::LineIcon(IconEnum::PLUS)->message("Project '%s > %s' | %s | %s",
                    $repoInfo->getUsername(), $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch(),
                    $repoInfo->isGitHubAction() ? "Actions workflow ✔" : "no setup X"
                );
                // handle send command to build
                if ($repoInfo->isGitHubAction()) {
                    (new Process("build project " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
                        sprintf("cd '%s'", $repoInfo->getCurrentRepositoryDir()),
                        sprintf('gh workflow run workflow--%s--%s -r %s', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch(), $repoInfo->getCurrentBranch())
                    ]))->execMultiInWorkDir();
                    // check completed
                    $startTime = new DateTime();
                    $lastSendingMinute = 0;
                    sleep(30); // wait A seconds while Actions handling new workflow
                    while (self::isActionsWorkflowQueuedOrInProgress($repoInfo)) {
                        $duration = new Duration($startTime->diff(new DateTime()));
                        $message = sprintf("Project build in progress (%s) ...", $duration->getText());
                        self::LineIcon(IconEnum::DOT)->setIndentLevel(IndentLevelEnum::ITEM_LINE)
                            ->message($message);
                        if ($duration->totalMinutes && $duration->totalMinutes > $lastSendingMinute && $duration->totalMinutes % 3 === 0) { // notify every A minutes
                            SlackService::sendMessageInternalUsing(sprintf("    %s %s", IconEnum::DOT, $message), $repoInfo->getRepositoryName(), $branchToBuild);
                            $lastSendingMinute =   $duration->totalMinutes;
                        }
                        sleep(30); // loop with interval = A seconds
                    }
                    self::LineIcon(IconEnum::CHECK)->setIndentLevel(IndentLevelEnum::ITEM_LINE)
                        ->message("build done");
                }
            }
        } // end loop
        //    notify
        SlackService::sendMessageInternalUsing(sprintf("[END] %s", CommandEnum::SUPPORT_COMMANDS()[CommandEnum::BUILD_ALL_PROJECTS][0]), DirHelper::getProjectDirName(), $branchToBuild);
    }


    private static function isActionsWorkflowQueuedOrInProgress(GitHubRepositoryInfo $repoInfo): bool
    {
        // in progress
        $resultInProgress = (new Process("check status of Actions workflow " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
            sprintf("cd '%s'", $repoInfo->getCurrentRepositoryDir()),
            sprintf('gh run list --workflow workflow--%s--%s.yml --status in_progress --json workflowName,status', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch())
        ]))->execMultiInWorkDirAndGetOutputStr();
        // queue
        $resultQueued = (new Process("check status of Actions workflow " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
            sprintf("cd '%s'", $repoInfo->getCurrentRepositoryDir()),
            sprintf('gh run list --workflow workflow--%s--%s.yml --status queued --json workflowName,status', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch())
        ]))->execMultiInWorkDirAndGetOutputStr();
        //
        return count(json_decode($resultInProgress, true)) || count(json_decode($resultQueued, true));
    }
}

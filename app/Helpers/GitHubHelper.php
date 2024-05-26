<?php

namespace App\Helpers;

use App\Classes\Base\CustomCollection;
use App\Classes\Duration;
use App\Classes\GitHubRepositoryInfo;
use App\Classes\Process;
use App\Enum\CommandEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Services\SlackService;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;
use DateTime;

/**
 * This is a GitHub helper
 */
class GitHubHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

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

    /**
     * @return string|null
     */
    public static function getCurrentBranch(): ?string
    {
        return (new Process(__FUNCTION__, DirHelper::getWorkingDir(), [
            GitHubEnum::GET_BRANCH_COMMAND
        ]))->execMultiInWorkDirAndGetOutputStrAll();
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

    public static function getRepository(): string
    {
        return basename(str_replace('.git', '', exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)));
    }

    /**
     * - Require envs: GITHUB_PERSONAL_ACCESS_TOKEN
     * - parameter priority: $customRepository > console arg > getenv()
     * @param string|null $customRepository
     * @param string|null $customBranch
     * @return void
     */
    public static function checkoutCaches(string $customRepository = null, string $customBranch = null): void
    {
        self::LineTag(TagEnum::GIT)->printTitle("Checkout The Repository In Caches Dir");
        // === validate ===
        //        env vars
        $repository = $customRepository ?: self::arg(1) ?: getenv('REPOSITORY');
        $branch = $customBranch ?: self::arg(2) ?: getenv('BRANCH');
        if ($repository === GitHubEnum::ENGAGE_API_DEPLOY) {
            $branch = $customBranch ?? getenv('API_DEPLOY_BRANCH');
        }
        $engagePlusCachesDir = getenv('ENGAGEPLUS_CACHES_DIR');
        $GitHubPersonalAccessToken = getenv('GITHUB_PERSONAL_ACCESS_TOKEN');

        if (!$repository || !$branch || !$engagePlusCachesDir || !$GitHubPersonalAccessToken) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->print("missing a REPOSITORY or BRANCH or ENGAGEPLUS_CACHES_DIR or GITHUB_PERSONAL_ACCESS_TOKEN");
            exit(); // END
        }

        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", $engagePlusCachesDir, $repository);
        //     message validate
        if ($customRepository) $repositoryFrom = "CODE";
        elseif (self::arg(1)) $repositoryFrom = "CONSOLE";
        elseif (getenv('REPOSITORY')) $repositoryFrom = "ENV";

        if ($customBranch) $branchFrom = "CODE";
        elseif (self::arg(2)) $branchFrom = "CONSOLE";
        elseif (getenv('BRANCH')) $branchFrom = "ENV";

        self::lineTag($repositoryFrom)->print("REPOSITORY = %s", $repository)
            ->setTag($branchFrom)->print("BRANCH = %s", $branch)
            ->setTag(null)->print("DIR = '$EngagePlusCachesRepositoryDir'");

        // === handle ===
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            self::LineNew()->print("The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository");
            //
            // case clone
        } else {
            self::LineTag(TagEnum::ERROR)->print("The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository");
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
        self::LineNew()->printTitle("Force checkout a GitHub repository with specific branch");
        // === input ===
        $GIT_URL_WITH_TOKEN = readline("Please input GIT_URL_WITH_TOKEN? ");
        if (!$GIT_URL_WITH_TOKEN) {
            self::LineTag(TagEnum::ERROR)->print("GitHub repository url with Token should be string");
            exit(); // END
        }
        $BRANCH_TO_FORCE_CHECKOUT = readline("Please input BRANCH_TO_FORCE_CHECKOUT? ");
        if (!$BRANCH_TO_FORCE_CHECKOUT) {
            self::LineTag(TagEnum::ERROR)->print("branch to force checkout should be string");
            exit(); // END
        }
        // === validation ===
        if (!(StrHelper::contains($GIT_URL_WITH_TOKEN, 'https://')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '@github.com')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '.git')
        )) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::FORMAT])
                ->print("invalid GitHub repository url with Token format, should be 'https://TOKEN_TOKEN@@github.com/USER_NAME/REPOSITORY.git'");
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
     * - Steps:
     *    1. get token from Secret (require aws credential)
     *    2. login gh with token
     *    3. run workflow
     * @return void
     */
    public static function buildAllProject()
    {
        $branchToBuild = GitHubEnum::DEVELOP;
        self::LineNew()->printTitle("Build all projects to keep the GitHub runner token connecting (develop env)");
        // validate
        //    workspace dir
        $workspaceDir = self::arg(1);
        if (!$workspaceDir) {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("require input the 'workspace directory' .e.g 'caches directory' or 'develop workspace directory'");
            return; //END
        }
        if (!is_dir($workspaceDir)) {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("Dir '%s' does not exist", $workspaceDir);
            return; //END
        }
        //    token
        $GitHubToken = AWSHelper::getValueEnvOpsSecretManager('GITHUB_PERSONAL_ACCESS_TOKEN');
        if (!$GitHubToken) {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("GitHub token not found (in Secret Manager)");
            return; //END
        }
        // handle
        //    notify
        SlackService::sendMessageInternal(sprintf("[BEGIN] %s", CommandEnum::SUPPORT_COMMANDS()[CommandEnum::BUILD_ALL_PROJECTS][0]), basename($workspaceDir), $branchToBuild);
        //    get GitHub token and login gh
        self::LineNew()->printSubTitle("login gh (GitHub CLI)");
        (new Process("login gh (GitHub CLI)", DirHelper::getWorkingDir(), [
            sprintf("echo %s | gh auth login --with-token", $GitHubToken),
        ]))->execMultiInWorkDir(true);
        //    send command to build all projects
        self::LineNew()->printSubTitle("send command to build all projects");
        self::LineNew()->print("WORKSPACE DIR = $workspaceDir");
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            $repoInfo->setCurrentWorkspaceDir($workspaceDir)->setCurrentBranch($branchToBuild);
            if (is_dir($repoInfo->getCurrentRepositoryDir())) {
                // show info
                self::LineIcon(IconEnum::PLUS)->print("Project '%s > %s' | %s | %s",
                    $repoInfo->getUsername(), $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch(),
                    $repoInfo->isGitHubAction() ? "Actions workflow ✔" : "no setup X"
                );
                // handle send command to build
                if ($repoInfo->isGitHubAction()) {
                    (new Process("build project " . $repoInfo->getRepositoryName(), $repoInfo->getCurrentRepositoryDir(), [
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
                            ->print($message);
                        if ($duration->totalMinutes && $duration->totalMinutes > $lastSendingMinute && $duration->totalMinutes % 3 === 0) { // notify every A minutes
                            SlackService::sendMessageInternal(sprintf("    %s %s", IconEnum::DOT, $message), $repoInfo->getRepositoryName(), $branchToBuild);
                            $lastSendingMinute = $duration->totalMinutes;
                        }
                        sleep(30); // loop with interval = A seconds
                    }
                    self::LineIcon(IconEnum::CHECK)->setIndentLevel(IndentLevelEnum::ITEM_LINE)
                        ->print("build done");
                }
            }
        } // end loop
        //    notify
        SlackService::sendMessageInternal(sprintf("[END] %s", CommandEnum::SUPPORT_COMMANDS()[CommandEnum::BUILD_ALL_PROJECTS][0]), basename($workspaceDir), $branchToBuild);
    }


    private static function isActionsWorkflowQueuedOrInProgress(GitHubRepositoryInfo $repoInfo): bool
    {
        // in progress
        $resultInProgress = (new Process("check status of Actions workflow " . $repoInfo->getRepositoryName(), $repoInfo->getCurrentRepositoryDir(), [
            sprintf('gh run list --workflow workflow--%s--%s.yml --status in_progress --json workflowName,status', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch())
        ]))->execMultiInWorkDirAndGetOutputStrAll();
        // queue
        $resultQueued = (new Process("check status of Actions workflow " . $repoInfo->getRepositoryName(), $repoInfo->getCurrentRepositoryDir(), [
            sprintf('gh run list --workflow workflow--%s--%s.yml --status queued --json workflowName,status', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch())
        ]))->execMultiInWorkDirAndGetOutputStrAll();
        //
        return count(json_decode($resultInProgress, true)) || count(json_decode($resultQueued, true));
    }

    public static function mergeFeatureAllConsole(): void
    {
        // validate
        //    repository
        if (GitHubHelper::getRepository() != GitHubEnum::MYOPS) {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("Invalid repository, only support %s repository", GitHubEnum::MYOPS);
            exitApp(ERROR_END);
        }
        //    branch
        $featureBranch = GitHubHelper::getCurrentBranch();
        if (!in_array($featureBranch, GitHubEnum::SUPPORT_BRANCHES)) {
            self::LineTagMultiple(TagEnum::VALIDATION_SUCCESS)->print("the branch '%s' is allow merge-feature-all", $featureBranch);
        } else {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("Invalid branch to merge feature all, should be a feature A branch | current branch is '%s'", $featureBranch);
            exitApp(ERROR_END);
        }
        // handle
        //    push new code to GitHub
        //        ask what news
        $whatNewsInput = ucfirst(readline("Please input the commit message:"));
        //        push
        (new Process("PUSH NEW RELEASE TO GITHUB", DirHelper::getWorkingDir(), [
            GitHubEnum::ADD_ALL_FILES_COMMAND, "git commit -m '$whatNewsInput'", GitHubEnum::PUSH_COMMAND,
        ]))->execMultiInWorkDir()->printOutput();
        //    checkout branches and push
        $commands = new CustomCollection();
        $supportBranches = collect([GitHubEnum::SUPPORT, GitHubEnum::SHIP, GitHubEnum::MASTER, GitHubEnum::STAGING, GitHubEnum::DEVELOP]);
        foreach ($supportBranches as $destinationBranch) {
            $commands->addStr("git checkout %s", $destinationBranch);
            $commands->addStr("git merge %s", $featureBranch);
            $commands->addStr("git push");
        }
        $commands->addStr("git checkout %s", $featureBranch);
        (new Process("Merge Feature All", DirHelper::getWorkingDir(), $commands))
            ->execMultiInWorkDir()->printOutput();
        // done
        self::lineTag(TagEnum::DONE)->setColor(UIEnum::COLOR_GREEN)->print("Merge feature '%s' to %s branches successfully", $featureBranch, $supportBranches->join(', '));
    }
}

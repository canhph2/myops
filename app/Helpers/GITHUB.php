<?php

namespace app\Helpers;

use app\Enum\GitHubEnum;
use app\Enum\TagEnum;
use app\Objects\Process;

/**
 * This is a GitHub helper
 */
class GITHUB
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
            $workingDir ?? DIR::getWorkingDir(),
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
            TEXT::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->message("missing a REPOSITORY or BRANCH or ENGAGEPLUS_CACHES_DIR or GITHUB_PERSONAL_ACCESS_TOKEN");
            exit(); // END
        }

        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", $engagePlusCachesDir, $repository);
        //     message validate
        TEXT::tag($param2 ? 'CUSTOM' : 'ENV')->message("REPOSITORY = %s", $repository)
            ->setTag($param3 ? 'CUSTOM' : 'ENV')->message("BRANCH = %s", $branch)
            ->message("DIR = '$EngagePlusCachesRepositoryDir'");

        // === handle ===
        TEXT::tag(TagEnum::GIT)->messageTitle("Handle Caches and Git");
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            TEXT::new()->message("The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository");
            //
            // case clone
        } else {
            TEXT::tag(TagEnum::ERROR)->message("The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository");
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
        TEXT::new()->messageTitle("Force checkout a GitHub repository with specific branch");
        // === input ===
        $GIT_URL_WITH_TOKEN = readline("Please input GIT_URL_WITH_TOKEN? ");
        if (!$GIT_URL_WITH_TOKEN) {
            TEXT::tag(TagEnum::ERROR)->message("GitHub repository url with Token should be string");
            exit(); // END
        }
        $BRANCH_TO_FORCE_CHECKOUT = readline("Please input BRANCH_TO_FORCE_CHECKOUT? ");
        if (!$BRANCH_TO_FORCE_CHECKOUT) {
            TEXT::tag(TagEnum::ERROR)->message("branch to force checkout should be string");
            exit(); // END
        }
        // === validation ===
        if (!(STR::contains($GIT_URL_WITH_TOKEN, 'https://')
            && STR::contains($GIT_URL_WITH_TOKEN, '@github.com')
            && STR::contains($GIT_URL_WITH_TOKEN, '.git')
        )) {
            TEXT::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::FORMAT])
                ->message("invalid GitHub repository url with Token format, should be 'https://TOKEN_TOKEN@@github.com/USER_NAME/REPOSITORY.git'");
            exit(); // END
        }
        // === handle ===
        $initGitCommands = self::isGit(DIR::getWorkingDir()) ? [] : [GitHubEnum::INIT_REPOSITORY_COMMAND];
        $setRemoteOriginUrlCommand = exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)
            ? sprintf("git remote set-url origin %s", $GIT_URL_WITH_TOKEN)
            : sprintf("git remote add origin %s", $GIT_URL_WITH_TOKEN);
        (new Process("Set repository remote url and force checkout branch", DIR::getWorkingDir(), array_merge($initGitCommands, [
            $setRemoteOriginUrlCommand,
            GitHubEnum::PULL_COMMAND,
            GitHubEnum::RESET_BRANCH_COMMAND,
            sprintf("git checkout -f %s", $BRANCH_TO_FORCE_CHECKOUT),
            GitHubEnum::PULL_COMMAND,
        ])))->execMultiInWorkDir(true)->printOutput();
        // === validate result ===
        (new Process("Validate branch", DIR::getWorkingDir(), [
            GitHubEnum::GET_BRANCH_COMMAND
        ]))->execMultiInWorkDir()->printOutput();
    }

    /**
     * [GitHub Actions]
     * @return void
     */
    public static function buildAllProject(){
        // todo build All project
    }
}

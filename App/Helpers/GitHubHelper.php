<?php

namespace App\Helpers;

use App\Enum\GitHubEnum;
use App\Objects\Process;

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
        $remoteOriginUrl = $remoteOriginUrl ?? self::getRemoteOriginUrl();
        return [
            $remoteOriginUrl,
            strpos($remoteOriginUrl, "@") !== false
                ? str_replace('https://', '', explode('@', $remoteOriginUrl)[0])
                : null,
            basename(str_replace(basename($remoteOriginUrl), '', $remoteOriginUrl)),
            basename(str_replace('.git', '', $remoteOriginUrl))
        ];
    }

    public static function getRemoteOriginUrl(): ?string
    {
        return exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND);
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
        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", getenv('ENGAGEPLUS_CACHES_DIR'), $repository);
        $GitHubPersonalAccessToken = getenv('GITHUB_PERSONAL_ACCESS_TOKEN');

        if (!$repository || !$branch || !$EngagePlusCachesRepositoryDir || !$GitHubPersonalAccessToken) {
            TextHelper::messageERROR("[ENV] missing a BRANCH or BRANCH or ENGAGEPLUS_CACHES_REPOSITORY_DIR or GITHUB_PERSONAL_ACCESS_TOKEN");
            exit(); // END
        }
        //     message validate
        TextHelper::message(sprintf("[%s] REPOSITORY = %s", $param2 ? 'CUSTOM' : 'ENV', $repository));
        TextHelper::message(sprintf("[%s] BRANCH = %s", $param3 ? 'CUSTOM' : 'ENV', $branch));
        TextHelper::message("DIR = '$EngagePlusCachesRepositoryDir'");

        // === handle ===
        //     var
        $remoteOriginUrl = sprintf("https://%s@github.com/%s/%s.git", $GitHubPersonalAccessToken, GitHubEnum::GITHUB_REPOSITORIES[$repository], $repository);
        TextHelper::messageTitle("Handle Caches and Git");
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            TextHelper::message("The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository");
            //
            // case clone
        } else {
            TextHelper::messageERROR("The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository");
            //
            (new Process("Remove old directory", null, [
                sprintf("rm -rf \"%s\"", $EngagePlusCachesRepositoryDir),
                sprintf("mkdir -p \"%s\"", $EngagePlusCachesRepositoryDir),
            ]))->execMulti()->printOutput();
            //
            (new Process("CLONE SOURCE CODE", $EngagePlusCachesRepositoryDir, [
                sprintf("git clone -b %s %s .", $branch, $remoteOriginUrl),
            ]))->execMultiInWorkDir(true)->printOutput();
        }
        // === update new code ===
        (new Process("UPDATE SOURCE CODE", $EngagePlusCachesRepositoryDir, [
            sprintf("git remote set-url origin %s", $remoteOriginUrl),
            GitHubEnum::RESET_BRANCH_COMMAND,
            sprintf("git checkout %s", $branch),
            GitHubEnum::PULL_COMMAND
        ]))->execMultiInWorkDir()->printOutput();
    }
}

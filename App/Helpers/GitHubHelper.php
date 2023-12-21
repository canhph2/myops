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
     * case engage-api-deploy, to build api docker image
     *     php _ops/lib_temp/HandleCachesAndGit ENGAGE_API_DEPLOY
     */
    public static function handleCachesAndGit(array $argv)
    {
        // === param ===
        $param2 = $argv[2] ?? null;
        // === validate ===
        //    validate env vars
        $repository = $param2 === 'ENGAGE_API_DEPLOY' ? 'engage-api-deploy' : getenv('Repository');
        $branch = $param2 === 'ENGAGE_API_DEPLOY' ? getenv('API_DEPLOY_BRANCH') : getenv('Branch');
        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", getenv('ENGAGEPLUS_CACHES_DIR'), $repository);
        $GitHubPersonalAccessToken = getenv('GITHUB_PERSONAL_ACCESS_TOKEN');

        if (!$repository || !$branch || !$EngagePlusCachesRepositoryDir || !$GitHubPersonalAccessToken) {
            echo "[ERROR] missing a Branch or Repository or ENGAGEPLUS_CACHES_REPOSITORY_DIR or GITHUB_PERSONAL_ACCESS_TOKEN\n";
            exit(); // END
        }

        // === handle case engage-api-deploy ===

        // === handle ===
        echo "===\n";
        echo "=== HANDLE CACHES AND GIT ===\n";
        echo "Repository=$repository    Branch=$branch   DIR='$EngagePlusCachesRepositoryDir' \n";
        //
        $gitRemoteURLWithToken = sprintf("https://%s@github.com/infohkengage/%s.git", $GitHubPersonalAccessToken, $repository);
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            echo "The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository\n";
            //
            // case clone
        } else {
            echo "[ERROR] The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository\n";
            $output = null;
            $resultCode = null;
            exec(join(';', [
                sprintf("rm -rf \"%s\"", $EngagePlusCachesRepositoryDir),
                sprintf("mkdir -p \"%s\"", $EngagePlusCachesRepositoryDir),
                sprintf("cd \"%s\"", $EngagePlusCachesRepositoryDir), # jump into this directory
                sprintf("git clone -b %s %s .", $branch, $gitRemoteURLWithToken),
            ]), $output, $resultCode);
            // print output
            foreach ($output as $line) {
                echo sprintf("    + %s\n", $line);
            }
        }
        // === update new code ===
        $output = null;
        $resultCode = null;
        exec(join(';', [
            sprintf("cd \"%s\"", $EngagePlusCachesRepositoryDir), # jump into this directory
            sprintf("git remote set-url origin %s", $gitRemoteURLWithToken),
            'git reset --hard HEAD',
            sprintf("git checkout %s", $branch),
            'git pull',
        ]), $output, $resultCode);
        // print output
        foreach ($output as $line) {
            echo sprintf("    + %s\n", $line);
        }
        echo "===\n\n";

    }
}

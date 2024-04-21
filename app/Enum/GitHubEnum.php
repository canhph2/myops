<?php

namespace App\Enum;

use App\Classes\GitHubRepositoryInfo;

class GitHubEnum
{
    // === GitHub commands ===
    public const INIT_REPOSITORY_COMMAND = 'git init';
    public const RESET_BRANCH_COMMAND = 'git reset --hard HEAD'; // rollback all changing
    public const GET_BRANCH_COMMAND = "git symbolic-ref HEAD | sed 's/refs\/heads\///g'";
    public const PULL_COMMAND = 'git pull'; // get the newest code
    public const ADD_ALL_FILES_COMMAND = 'git add -A';
    public const PUSH_COMMAND = 'git push';
    public const GET_REMOTE_ORIGIN_URL_COMMAND = 'git config --get remote.origin.url';
    public const GET_REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
    public const GET_HEAD_COMMIT_ID_COMMAND = 'git rev-parse --short HEAD';

    // === Git branches ===
    public const MAIN = 'main';
    public const MASTER = 'master';
    public const STAGING = 'staging';
    public const DEVELOP = 'develop';

    /**
     * @return array
     */
    public static function GET_REPOSITORIES_INFO(): array
    {
        return [
            new GitHubRepositoryInfo('engage-api', 'infohkengage', true),
            new GitHubRepositoryInfo('engage-spa', 'infohkengage', true),
            new GitHubRepositoryInfo('engage-booking-api', 'infohkengage', true),
            new GitHubRepositoryInfo('engage-booking-spa', 'infohkengage', true),
            new GitHubRepositoryInfo('invoice-service', 'infohkengage', true),
            new GitHubRepositoryInfo('payment-service', 'infohkengage', true),
            new GitHubRepositoryInfo('integration-api', 'infohkengage', true),
            new GitHubRepositoryInfo('email-service', 'infohkengage', true),
            //
            new GitHubRepositoryInfo('engage-api-deploy', 'infohkengage'),
            //
            new GitHubRepositoryInfo('engage-database-utils', 'congnqnexlesoft'),
            new GitHubRepositoryInfo('myops', 'congnqnexlesoft'),
            new GitHubRepositoryInfo('docker-base-images', 'congnqnexlesoft'),
            new GitHubRepositoryInfo('engage-selenium-test-1', 'congnqnexlesoft'),
        ];
    }
}

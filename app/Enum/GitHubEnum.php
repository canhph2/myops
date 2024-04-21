<?php

namespace App\Enum;

use App\Classes\GitHubRepositoryInfo;

class GitHubEnum
{
    // === GitHub commands ===
    const INIT_REPOSITORY_COMMAND = 'git init';
    const RESET_BRANCH_COMMAND = 'git reset --hard HEAD'; // rollback all changing
    const GET_BRANCH_COMMAND = "git symbolic-ref HEAD | sed 's/refs\/heads\///g'";
    const PULL_COMMAND = 'git pull'; // get the newest code
    const ADD_ALL_FILES_COMMAND = 'git add -A';
    const PUSH_COMMAND = 'git push';
    const GET_REMOTE_ORIGIN_URL_COMMAND = 'git config --get remote.origin.url';
    const GET_REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
    const GET_HEAD_COMMIT_ID_COMMAND = 'git rev-parse --short HEAD';

    // === Git branches ===
    const MAIN = 'main';
    const MASTER = 'master';
    const STAGING = 'staging';
    const DEVELOP = 'develop';
    const SUPPORT_BRANCHES = [self::MAIN, self::MASTER, self::STAGING, self::DEVELOP];

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

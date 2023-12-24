<?php

namespace app\Enum;

class GitHubEnum
{
    public const INIT_REPOSITORY_COMMAND = 'git init';
    public const RESET_BRANCH_COMMAND = 'git reset --hard HEAD'; // rollback all changing
    public const GET_BRANCH_COMMAND = "git symbolic-ref HEAD | sed 's/refs\/heads\///g'";
    public const PULL_COMMAND = 'git pull'; // get the newest code
    public const ADD_ALL_FILES_COMMAND = 'git add -A';
    public const PUSH_COMMAND = 'git push';
    public const GET_REMOTE_ORIGIN_URL_COMMAND = 'git config --get remote.origin.url';
    public const GET_REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
    public const GET_HEAD_COMMIT_ID_COMMAND = 'git rev-parse --short HEAD';

    /**
     * key => value  | key = GitHub project name, value =  GitHub username
     */
    public const GITHUB_REPOSITORIES = [
        'engage-api' => 'infohkengage',
        'engage-spa' => 'infohkengage',
        'engage-booking-api' => 'infohkengage',
        'engage-booking-spa' => 'infohkengage',
        'invoice-service' => 'infohkengage',
        'payment-service' => 'infohkengage',
        'integration-api' => 'infohkengage',
        'email-service' => 'infohkengage',
        //
        'engage-api-deploy' => 'infohkengage',
        //
        'engage-database-utils' => 'congnqnexlesoft',
        'ops-lib' => 'congnqnexlesoft',
        'docker-base-images' => 'congnqnexlesoft',
    ];
}

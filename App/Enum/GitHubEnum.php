<?php

namespace App\Enum;

class GitHubEnum
{
    public const REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
    public const RESET_BRANCH_COMMAND = 'git reset --hard HEAD'; // rollback all changing
    public const GET_BRANCH_COMMAND = "git symbolic-ref HEAD | sed 's/refs\/heads\///g'";
    public const PULL_COMMAND = 'git pull'; // get the newest code
    public const ADD_ALL_FILES_COMMAND = 'git add -A';
    public const PUSH_COMMAND = 'git push';
    public const GET_REMOTE_ORIGIN_URL_COMMAND = 'git config --get remote.origin.url';
    public const GET_REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
}

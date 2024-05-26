<?php

namespace App\Factories;

use App\Classes\Base\CustomCollection;
use App\Enum\GitHubEnum;

class GitHubFactory
{
    /**
     * @param string $branch
     * @return string
     */
    public static function generateCheckoutCommand(string $branch): string
    {
        return sprintf(GitHubEnum::CHECKOUT_COMMAND, $branch, $branch);
    }

    /**
     * @param string $message
     * @return string
     */
    public static function generateCommitCommand(string $message): string
    {
        return sprintf(GitHubEnum::COMMIT_COMMAND, $message);
    }

    /**
     * @param string $message
     * @return CustomCollection
     */
    public static function generateCommitAndPushCommands(string $message): CustomCollection
    {
        return collect([
            GitHubEnum::ADD_ALL_FILES_COMMAND, self::generateCommitCommand($message), GitHubEnum::PUSH_COMMAND,
        ]);
    }

    /**
     * @param string $branch
     * @param bool $isClean
     * @return CustomCollection
     */
    public static function generateCheckoutCommands(string $branch, bool $isClean = false): CustomCollection
    {
        return ($isClean ? collect([GithubEnum::CLEAN_COMMAND]) : collect())->merge([
            GitHubEnum::RESET_BRANCH_COMMAND,
            self::generateCheckoutCommand($branch),
            GitHubEnum::PULL_COMMAND
        ]);
    }
}

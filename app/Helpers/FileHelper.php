<?php

namespace App\Helpers;

use App\Classes\Base\CustomCollection;
use App\Classes\Process;
use App\Enum\GitHubEnum;
use App\Enum\SharedFileEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;
use Exception;

/**
 * (Last updated on August 15, 2024)
 * ### A File Helper (MyOps)
 */
class FileHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    /**
     * - Conditions:
     *   - File list will be the destination project's file list
     *   - The same file should exist in source project
     * - Also checking difference
     * @return CustomCollection item format ['index' => AA, 'source' => BB, 'destination' => CC]
     * @throws Exception
     */
    private static function generateProjectSharedFilesList(string $sourceProjectName, string $destinationProject): CustomCollection
    {
        // validate
        $sourceRepoInfo = GitHubHelper::getRepositoryInfoByName($sourceProjectName);
        $destinationRepoInfo = GitHubHelper::getRepositoryInfoByName($destinationProject);
        if (!$sourceRepoInfo) {
            throw new Exception("repository $sourceProjectName not found"); // END
        }
        if (!$destinationRepoInfo) {
            throw new Exception("repository $destinationProject not found"); // END
        }
        // handle
        $sourceRepoInfo->setCurrentWorkspaceDir(DirHelper::getWorkspaceDir());
        $destinationRepoInfo->setCurrentWorkspaceDir(DirHelper::getWorkspaceDir());
        return (new CustomCollection(SharedFileEnum::LIST))->map(function ($path)
        use ($sourceRepoInfo, $destinationRepoInfo) {
            $sourcePath = DirHelper::join($sourceRepoInfo->getCurrentRepositorySourceDir(), $path);
            $destinationPath = DirHelper::join($destinationRepoInfo->getCurrentRepositorySourceDir(), $path);
            $validIndexAndChange = is_file($sourcePath) && is_file($destinationPath)
                && file_get_contents($sourcePath) !== file_get_contents($destinationPath);
            return $validIndexAndChange ? [
                'index' => $path,
                'source' => DirHelper::join($sourceRepoInfo->getCurrentRepositorySourceDir(), $path),
                'destination' => DirHelper::join($destinationRepoInfo->getCurrentRepositorySourceDir(), $path),
            ] : [];
        })->filterEmpty();
    }

    /**
     * - The file list will get destination project file list
     * @return void
     * @throws Exception
     */
    public static function syncSharedCodeFiles(): void
    {
        // validate
        $sourceProjectName = readline(sprintf("Please input source project (default '%s'): ", GitHubEnum::ENGAGE_API));
        $sourceProjectName = $sourceProjectName ?: GitHubEnum::ENGAGE_API;
        //   source project
        if (!in_array($sourceProjectName, GitHubEnum::BACKEND_REPOSITORIES)) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)
                ->print("Un-support source project '%s'", $sourceProjectName);
            exitApp(ERROR_END);
        }
        //   destination project
        $destinationProjectName = DirHelper::getProjectDirName();
        if (!in_array($destinationProjectName, GitHubEnum::BACKEND_REPOSITORIES)) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)
                ->print("Un-support destination project '%s'", $destinationProjectName);
            exitApp(ERROR_END);
        }
        //   same project
        if ($sourceProjectName === $destinationProjectName) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)
                ->print("Source project '%s' is same destination project '%s'", $sourceProjectName, $destinationProjectName);
            exitApp(ERROR_END);
        }
        // handle
        self::lineNew()->printTitle("Sync shared code files from project '%s' to project '%s'",
            $sourceProjectName, $destinationProjectName);
        $copyCommands = self::generateProjectSharedFilesList($sourceProjectName, $destinationProjectName)->map(function ($item) {
            return sprintf("cp -f '%s' '%s'", $item['source'], $item['destination']);
        });
        (new Process("copy shared fields", DirHelper::getWorkingDir(), $copyCommands))
            ->execMultiInWorkDir()->printOutput();
        //
        self::lineNew()->printSeparatorLine()
            ->setTag(TagEnum::SUCCESS)
            ->print("The new fields have been successfully copied to project '%s'", $destinationProjectName)
            ->setTagMultiple(TagEnum::ACTION_REQUIRED)->setColor(UIEnum::COLOR_RED)
            ->print('Please review the file and commit the changes');
    }
}

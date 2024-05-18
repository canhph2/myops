<?php

namespace App\Helpers;

use App\Classes\Base\CustomCollection;
use App\Classes\Process;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Enum\ValidationTypeEnum;
use App\Factories\ShellFactory;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

/**
 * Last modified on May 18, 2024.
 * this is a DIRectory helper / folder helper
 */
class DirHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    /**
     * get home directory / get root directory of user
     *
     * @param string|null $withSubDirOrFile
     * @return string
     */
    public static function getHomeDir(string $withSubDirOrFile = null): string
    {
        return $withSubDirOrFile
            ? sprintf("%s/%s", $_SERVER['HOME'], $withSubDirOrFile)
            : $_SERVER['HOME'];
    }

    /**
     * @param ...$subDirOrFiles
     * @return string
     */
    public static function getWorkingDir(...$subDirOrFiles): string
    {
        return count($subDirOrFiles) ? self::join($_SERVER['PWD'], ...$subDirOrFiles) : $_SERVER['PWD'];
    }

    /**
     * @return string
     */
    public static function getProjectDirName(): string
    {
        return basename(self::getWorkingDir());
    }

    /**
     * get current working directory of script
     * @return string
     */
    public static function getScriptDir(): string
    {
        $scriptDir = substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - strlen(basename($_SERVER['SCRIPT_FILENAME'])) - 1);
        return self::getWorkingDir($scriptDir);
    }

    /**
     * @return string
     */
    public static function getScriptFullPath(): string
    {
        return self::getWorkingDir($_SERVER['SCRIPT_FILENAME']);
    }

    // backup code
//    public static function getRepositoryDir()
//    {
//        return exec('git rev-parse --show-toplevel');
//    }

    /**
     * usage: <name>::join($part1, $part2, $morePart) -> "$part1/$part2/$morePart"
     * @param ...$dirOrFileParts
     * @return string|null
     */
    public static function join(...$dirOrFileParts): ?string
    {
        return join('/', array_filter($dirOrFileParts, function ($item) {
            return $item; // filter null or empty parts
        }));
    }

    /**
     * - Parameter priority: custom > console
     * - handle tmp directory
     *    - tmp add : create a tmp directory
     *    - tmp remove : remove the tmp directory
     *
     * @param string|null $customSubCommand
     * @param mixed ...$customSubDirs
     * @return void
     */
    public static function tmp(string $customSubCommand = null, ...$customSubDirs): void
    {
        $subCommand = $customSubCommand ?? self::arg(1);
        $subDirs = count($customSubDirs) ? new CustomCollection($customSubDirs): self::inputArr('sub-dir');
        switch ($subCommand) {
            case 'add':
                // handle
                //    single dir
                $commands = ShellFactory::generateMakeDirCommand(self::getWorkingDir('tmp'));
                //    multiple sub-dir
                foreach ($subDirs as $subDir) {
                    $commands->merge(ShellFactory::generateMakeDirCommand(self::getWorkingDir($subDir, 'tmp')));
                }
                //    execute
                (new Process("Add tmp dir", self::getWorkingDir(), $commands))
                    ->execMultiInWorkDir()->printOutput();
                // validate the result
                self::validateDirOrFileExisting(ValidationTypeEnum::EXISTS, self::getWorkingDir(), 'tmp');
                foreach ($subDirs as $subDir) {
                    self::validateDirOrFileExisting(ValidationTypeEnum::EXISTS, self::getWorkingDir($subDir), 'tmp');
                }
                break;
            case 'remove':
                // handle
                //    single dir
                $commands = ShellFactory::generateRemoveDirCommand(self::getWorkingDir('tmp'));
                //    multiple sub-dir
                foreach ($subDirs as $subDir) {
                    $commands->merge(ShellFactory::generateRemoveDirCommand(self::getWorkingDir($subDir, 'tmp')));
                }
                //    execute
                (new Process("Remove tmp dir", self::getWorkingDir(), $commands))
                    ->execMultiInWorkDir()->printOutput();
                // validate the result
                DirHelper::validateDirOrFileExisting(ValidationTypeEnum::DONT_EXISTS, self::getWorkingDir(), 'tmp');
                foreach ($subDirs as $subDir) {
                    self::validateDirOrFileExisting(ValidationTypeEnum::DONT_EXISTS, self::getWorkingDir($subDir), 'tmp');
                }
                break;
            default:
                self::LineTag(TagEnum::ERROR)->print("missing action, action should be 'add' or 'remove'");
                break;
        }
    }

    /**
     * .e.g usage   DIR::getClassPath(TextLine::class)
     * class name should follow PSR-4
     * @param string $ClassDotClass
     * @return void
     */
    public static function getClassPathAndFileName(string $ClassDotClass): string
    {
        return lcfirst(sprintf("%s.php", str_replace("\\", "/", $ClassDotClass)));
    }

    /**
     * Parameters priority: custom > console
     * @param string $type
     * @param string|null $customDirToCheck1
     * @param mixed ...$customFileOrDirToValidate1
     * @return void
     */
    public static function validateDirOrFileExisting(string $type = ValidationTypeEnum::EXISTS, string $customDirToCheck1 = null, ...$customFileOrDirToValidate1)
    {
        // validate
        $dirToCheck1 = $customDirToCheck1 ?? self::arg(2);
        $fileOrDirToValidate1 = count($customFileOrDirToValidate1) ? new CustomCollection($customFileOrDirToValidate1) : self::args(2);
        if (!$dirToCheck1 || $fileOrDirToValidate1->isEmpty()) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])->print("missing 'dirToCheck' or 'fileOrDirToValidate' (can path multiple fileOrDir1 fileOrDir2)");
            exit(1); // END
        }
        if (!is_dir($dirToCheck1)) {
            self::LineTag(TagEnum::ERROR)->print(" dir '%s' does not exist", $dirToCheck1);
            exit(1); // END
        }
        // handle
        $dirToCheck1FilesAndDirs = scandir($dirToCheck1);
        //    case: exist
        if ($type === ValidationTypeEnum::EXISTS) {
            $invalid = false;
            foreach ($fileOrDirToValidate1 as $fileOrDir) {
                if (in_array($fileOrDir, $dirToCheck1FilesAndDirs)) {
                    self::lineIcon(IconEnum::CHECK)->setTagMultiple(TagEnum::VALIDATION_SUCCESS)
                        ->print("'%s' is existing in dir '%s'", $fileOrDir, $dirToCheck1);
                } else {
                    $invalid = true;
                    self::lineIcon(IconEnum::X)->setTagMultiple(TagEnum::VALIDATION_ERROR)
                        ->print("'%s' isn't existing in dir '%s'", $fileOrDir, $dirToCheck1);
                }
            }
            if ($invalid) {
                exit(1); // END
            }
        }
        //    case: don't exist
        if ($type === ValidationTypeEnum::DONT_EXISTS) {
            $invalid = false;
            foreach ($fileOrDirToValidate1 as $fileOrDir) {
                if (in_array($fileOrDir, $dirToCheck1FilesAndDirs)) {
                    self::lineIcon(IconEnum::X)->setTagMultiple(TagEnum::VALIDATION_ERROR)
                        ->print("'%s' is existing in dir '%s'", $fileOrDir, $dirToCheck1);
                    $invalid = true;
                } else {
                    self::lineIcon(IconEnum::CHECK)->setTagMultiple(TagEnum::VALIDATION_SUCCESS)
                        ->print("'%s' isn't existing in dir '%s'", $fileOrDir, $dirToCheck1);
                }
            }
            if ($invalid) {
                exit(1); // END
            }
        }
    }

    public static function validateFileContainsText(string $customFilePath = null, ...$customSearchTexts)
    {
        // validate
        $filePath = $customFilePath ?? self::arg(2);
        $searchTexts = count($customSearchTexts) ? new CustomCollection($customSearchTexts) : self::args(2);
        if (!$filePath || $searchTexts->isEmpty()) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])->print("missing filePath or searchText (can path multiple searchText1 searchText2)");
            exit(1); // END
        }
        if (!is_file($filePath)) {
            self::LineTag(TagEnum::ERROR)->print("'%s' does not exist", $filePath);
            exit(1); // END
        }
        // handle
        $fileContent = file_get_contents($filePath);
        $validationResult = [];
        foreach ($searchTexts as $searchText) {
            $validationResult[] = [
                'searchText' => $searchText,
                'isContains' => StrHelper::contains($fileContent, $searchText)
            ];
        }
        $amountValidationPass = count(array_filter($validationResult, function ($item) {
            return $item['isContains'];
        }));
        if ($amountValidationPass === $searchTexts->count()) {
            self::LineTagMultiple(TagEnum::VALIDATION_SUCCESS)->print("file '%s' contains text(s): '%s'", $filePath, join("', '", $searchTexts->toArr()));
        } else {
            self::LineTagMultiple(TagEnum::VALIDATION_ERROR)->print("file '%s' does not contains (some) text(s):", $filePath);
            foreach ($validationResult as $result) {
                self::LineIndent(IndentLevelEnum::ITEM_LINE)
                    ->setIcon($result['isContains'] ? IconEnum::CHECK : IconEnum::X)
                    ->setColor($result['isContains'] ? UIEnum::COLOR_GREEN : UIEnum::COLOR_RED)
                    ->print($result['searchText']);
            }
            exit(1); // END
        }
    }


}

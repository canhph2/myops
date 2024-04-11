<?php

namespace App\Helpers;

use App\Enum\TagEnum;
use App\Classes\Process;

/**
 * this is a DIRectory helper / folder helper
 */
class DirHelper
{
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
     * @param string|null $withSubDirOrFile
     * @return string
     */
    public static function getWorkingDir(string $withSubDirOrFile = null): string
    {
        return $withSubDirOrFile
            ? sprintf("%s/%s", $_SERVER['PWD'], $withSubDirOrFile)
            : $_SERVER['PWD'];
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

    // backup code
//    public static function getRepositoryDir()
//    {
//        return exec('git rev-parse --show-toplevel');
//    }

    /**
     * handle tmp directory
     * - tmp add : create a tmp directory
     * - tmp remove : remove the tmp directory
     *
     * @param array $argv
     * @return void
     */
    public static function tmp(array $argv): void
    {
        switch ($argv[2] ?? null) {
            case 'add':
                if (is_dir(self::getWorkingDir('tmp'))) {
                    $commands[] = sprintf("rm -rf '%s'", self::getWorkingDir('tmp'));
                }
                $commands[] = sprintf("mkdir -p '%s'", self::getWorkingDir('tmp'));
                (new Process("Add tmp dir", self::getWorkingDir(), $commands))
                    ->execMultiInWorkDir()->printOutput();
                // validate result
                TextHelper::new()->messageCondition(is_dir(self::getWorkingDir('tmp')),
                    'create a tmp dir successfully', 'create a tmp dir failure');
                break;
            case 'remove':
                if (is_dir(self::getWorkingDir('tmp'))) {
                    $commands[] = sprintf("rm -rf '%s'", self::getWorkingDir('tmp'));
                    (new Process("Remove tmp dir", self::getWorkingDir(), $commands))
                        ->execMultiInWorkDir()->printOutput();
                    // validate result
                    $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'tmp'", self::getWorkingDir()));
                    TextHelper::new()->messageCondition(!$checkTmpDir,
                        'remove a tmp dir successfully', 'remove a tmp dir failure');
                } else {
                    TextHelper::new()->message("tmp directory doesn't exist, do nothing");
                }
                break;
            default:
                TextHelper::tag(TagEnum::ERROR)->message("missing action, action should be 'add' or 'remove'");
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


}

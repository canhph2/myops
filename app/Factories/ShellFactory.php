<?php

namespace App\Factories;

use App\Classes\Base\CustomCollection;

class ShellFactory
{
    /**
     * @param string $dirFullPath
     * @param bool $isRemoveOldDir
     * @return CustomCollection
     */
    public static function generateMakeDirCommand(string $dirFullPath, bool $isRemoveOldDir = true): CustomCollection
    {
        $commands = new CustomCollection();
        if ($isRemoveOldDir && is_dir($dirFullPath)) {
            $commands->addStr("rm -rf '%s'", $dirFullPath);
        }
        return $commands->addStr("mkdir -p '%s'", $dirFullPath);
    }

    /**
     * @param string $fileOrDirFullPath
     * @return CustomCollection
     */
    public static function generateRemoveFileOrDirCommand(string $fileOrDirFullPath): CustomCollection
    {
        $commands = new CustomCollection();
        if (is_file($fileOrDirFullPath) || is_dir($fileOrDirFullPath)) {
            $commands->addStr("rm -rf '%s'", $fileOrDirFullPath);
        }
        return $commands;
    }
}

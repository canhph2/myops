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
     * @param string $dirFullPath
     * @return CustomCollection
     */
    public static function generateRemoveDirCommand(string $dirFullPath): CustomCollection
    {
        $commands = new CustomCollection();
        if (is_dir($dirFullPath)) {
            $commands->addStr("rm -rf '%s'", $dirFullPath);
        }
        return $commands;
    }
}

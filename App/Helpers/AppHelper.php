<?php

namespace App\Helpers;

class AppHelper
{
    /**
     * @param string $dirName
     * @return void
     */
    public static function requireOneAllPHPFilesInDir(string $dirName): void
    {
        $fullDirToScan = sprintf("%s/%s", DirHelper::getScriptDir(), $dirName);
        foreach (scandir($fullDirToScan) as $subDirName) {
            $fullSubDirToCheck = sprintf("%s/%s/%s", DirHelper::getScriptDir(), $dirName, $subDirName);
            if ($subDirName != '.' && $subDirName != '..' && is_dir($fullSubDirToCheck)) {
                $PHPFiles = glob("$fullSubDirToCheck/*.php");
                foreach ($PHPFiles as $PHPFile) {
                    require_once $PHPFile;
                }
                // check next
                AppHelper::requireOneAllPHPFilesInDir("$dirName/$subDirName");
            }
        }
    }
}

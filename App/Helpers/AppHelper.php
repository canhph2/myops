<?php

namespace App\Helpers;

use App\App;
use App\Objects\Release;
use App\Objects\Version;

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

    /**
     * this will increase App:APP_VERSION
     * this will push new code to GitHub
     * @return void
     */
    public static function increaseVersion()
    {
        $appClassPath = Release::FILES_LIST[count(Release::FILES_LIST) - 1];
        $appClassContent = file_get_contents($appClassPath);
        foreach (explode(PHP_EOL, $appClassContent) as $line) {
           if(strpos($line, 'const APP_VERSION =') !== false){
               $line = sprintf("    const APP_VERSION = '%s';", Version::parse(App::APP_VERSION)->bump()->toString());
           }
           $newArr[] = $line;
        }
       file_put_contents($appClassPath, join(PHP_EOL, $newArr));
    }
}

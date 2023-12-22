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
    public static function increaseVersion(string $part = Version::PATCH)
    {
        // handle version
        switch ($part) {
            case Version::MINOR:
                $newVersion = Version::parse(App::APP_VERSION)->bump(Version::MINOR);
                break;
            case Version::PATCH:
            default:
                $newVersion = Version::parse(App::APP_VERSION)->bump($part);
                break;
        }
        // update data
        //    App class
        $appClassPath = Release::FILES_LIST[count(Release::FILES_LIST) - 1];
        file_put_contents($appClassPath, preg_replace(
            '/APP_VERSION\s*=\s*\'(\d+\.\d+\.\d+)\'/',
            sprintf("APP_VERSION = '%s'", $newVersion->toString()),
            file_get_contents($appClassPath)
        ));
        //    README.MD
        $readmePath = "README.MD";
        file_put_contents($readmePath, preg_replace(
            '/ops-lib v(\d+\.\d+\.\d+)/',
            sprintf("ops-lib v%s", $newVersion->toString()),
            file_get_contents($readmePath)
        ));
    }
}

<?php

namespace App\Helpers;

use App\Classes\Version;
use App\Enum\AppInfoEnum;

class AppHelper
{
    /**
     * @param string $fullDirPath
     * @return void
     */
    public static function requireOneAllPHPFilesInDir(string $fullDirPath): void
    {
        foreach (scandir($fullDirPath) as $subDirName) {
            $fullSubDirToCheck = sprintf("%s/%s", $fullDirPath, $subDirName);
            if ($subDirName != '.' && $subDirName != '..' && is_dir($fullSubDirToCheck)) {
                $PHPFiles = glob("$fullSubDirToCheck/*.php");
                foreach ($PHPFiles as $PHPFile) {
                    require_once $PHPFile;
                }
                // check next
                AppHelper::requireOneAllPHPFilesInDir($fullSubDirToCheck);
            }
        }
    }


    /**
     * this will increase app:APP_VERSION
     * this will push new code to GitHub
     *
     * @param string $part
     * @return Version
     */
    public static function increaseVersion(string $part = Version::PATCH): Version
    {
        // handle version
        $isAddToVersionMD = false;
        switch ($part) {
            case Version::MINOR:
                $newVersion = Version::parse(AppInfoEnum::APP_VERSION)->bump(Version::MINOR);
                $isAddToVersionMD = true;
                break;
            case Version::PATCH:
            default:
                $newVersion = Version::parse(AppInfoEnum::APP_VERSION)->bump($part);
                break;
        }
        // update data
        //    app class
        file_put_contents(DirHelper::getClassPathAndFileName(AppInfoEnum::class), preg_replace(
            '/APP_VERSION\s*=\s*\'(\d+\.\d+\.\d+)\'/',
            sprintf("APP_VERSION = '%s'", $newVersion->toString()),
            file_get_contents(DirHelper::getClassPathAndFileName(AppInfoEnum::class))
        ));
        //    README.MD
        $readmePath = "README.MD";
        file_put_contents($readmePath, preg_replace(
            '/' . AppInfoEnum::APP_NAME . ' v(\d+\.\d+\.\d+)/',
            sprintf("%s v%s", AppInfoEnum::APP_NAME, $newVersion->toString()),
            file_get_contents($readmePath)
        ));
        //    VERSION.MD
        if ($isAddToVersionMD) {
            $VersionMDPath = "VERSION.MD";
            file_put_contents($VersionMDPath, str_replace(
                sprintf("## === v%s ===", $newVersion->getMajor()),
                sprintf("## === v%s ===\n- %s | TODO ADD SOME CHANGE LOGS", $newVersion->getMajor(), $newVersion->toString()),
                file_get_contents($VersionMDPath)
            ));
        }
        //
        return $newVersion;
    }
}

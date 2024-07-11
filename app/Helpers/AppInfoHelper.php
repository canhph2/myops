<?php

namespace App\Helpers;

use App\Classes\Base\CustomCollection;
use App\Classes\Process;
use App\Enum\DevelopmentEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\MyOps;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

class AppInfoHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    public static function printVersion(): void
    {
        // filter color
        if (self::arg(1) === 'no-format-color') {
            self::lineNew()->print(MyOps::getAppVersionStr());
        } else {
            // default
            self::lineColorFormat(UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD)->print(MyOps::getAppVersionStr());
        }
    }

    public static function info(): void
    {
        self::lineNew()->printTitle("project info");
        self::lineIcon(IconEnum::DOT)->print('OS               : %s', self::color(self::getOSVersion(), UIEnum::COLOR_BLUE));
        // validate
        $envPath = DirHelper::getWorkingDir(DevelopmentEnum::DOT_CONFIG_RYT);
        if (!is_file($envPath)) {
            self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print('%s not found', DevelopmentEnum::DOT_CONFIG_RYT);
            return; // END
        }
        // handle
        //    get env value
        $tempEnvs = new CustomCollection(StrHelper::findLinesContainsTextInFile($envPath, 'APP_NAME'));
        if (!getenv('SYS_ENV')) {
            $tempEnvs->merge(StrHelper::findLinesContainsTextInFile($envPath, 'SYS_ENV'));
        }
        if (!getenv('FRONTEND_URL')) {
            $tempEnvs->merge(StrHelper::findLinesContainsTextInFile($envPath, 'FRONTEND_URL'));
        }
        if (!getenv('DB_HOST')) {
            $tempEnvs->merge(StrHelper::findLinesContainsTextInFile($envPath, 'DB_HOST'));
        }
        if (!getenv('DB_DATABASE_API')) {
            $tempEnvs->merge(StrHelper::findLinesContainsTextInFile($envPath, 'DB_DATABASE_API'));
        }
        if (!getenv('DB_DATABASE_PAYMENT')) {
            $tempEnvs->merge(StrHelper::findLinesContainsTextInFile($envPath, 'DB_DATABASE_PAYMENT'));
        }
        foreach ($tempEnvs as $line) {
            putenv($line);
        }
        //    print
        //        app name
        $appNameFormat = self::colorFormat(ucwords(str_replace('-', ' ', getenv("APP_NAME"))),
            UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD);
        self::lineIcon(IconEnum::DOT)->print('app name         : %s', $appNameFormat);
        //        env
        self::lineIcon(IconEnum::DOT)->print('env              : %s', self::color(ENVHelper::getENVText(), UIEnum::COLOR_BLUE));
        //        url
        self::lineNew()->printSeparatorLine();
        self::lineIcon(IconEnum::DOT)->print('frontend URL     : %s', self::color(getenv("FRONTEND_URL"), UIEnum::COLOR_BLUE));
        //        database info
        self::lineNew()->printSeparatorLine();
        self::lineIcon(IconEnum::DOT)->print('database host    : %s', self::color(getenv("DB_HOST"), UIEnum::COLOR_BLUE));
        self::lineIcon(IconEnum::DOT)->print('database API     : %s', self::color(getenv("DB_DATABASE_API"), UIEnum::COLOR_BLUE));
        self::lineIcon(IconEnum::DOT)->print('database payment : %s', self::color(getenv("DB_DATABASE_PAYMENT"), UIEnum::COLOR_BLUE));
        //
        self::lineIndent(IndentLevelEnum::DECREASE)->printSeparatorLine();
    }

    /**
     * @return string
     */
    private static function getOSVersion(): string
    {
        // All Linux OS
        $LinuxOSReleaseInfoPath = '/etc/os-release';
        if (is_file($LinuxOSReleaseInfoPath)) {
            foreach (explode(PHP_EOL, file_get_contents($LinuxOSReleaseInfoPath)) as $line) {
                if(trim($line)) {
                    putenv($line);
                }
            }
            return trim(getenv('PRETTY_NAME'), "\"'"); // END
        }
        // Mac
        $macVersionInfo = (new Process('get mac version info', DirHelper::getWorkingDir(),
            ['sw_vers']))->execMultiInWorkDirAndGetOutputStrAll();
        if ($macVersionInfo) {
            foreach (explode(PHP_EOL, str_replace([':', ' ', "\t"], ['=', '', ''], $macVersionInfo)) as $line) {
                putenv($line);
            }
            return sprintf("%s v%s", getenv("ProductName"), getenv("ProductVersion")); // END
        }
        // others
        return 'error v0.0';
    }
}

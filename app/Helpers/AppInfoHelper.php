<?php

namespace App\Helpers;

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

    /** to use in raw shell (command) */
    public static function getVersionStr($isNoFormat = false, $isEndLine = true): string
    {
        if ($isNoFormat) {
            return MyOps::getAppVersionStr();
        } else {
            return self::colorFormat(MyOps::getAppVersionStr(), UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD, $isEndLine);
        }
    }
}

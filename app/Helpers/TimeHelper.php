<?php

namespace App\Helpers;

use App\Enum\TagEnum;
use App\Enum\TimeEnum;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;
use DateTimeImmutable;

class TimeHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    public static function handleTimeInConsole(): void
    {
        // validate
        ValidationHelper::validateSubCommandOrParam1('sub-command-of-time', TimeEnum::SUPPORT_SUB_COMMANDS);
        //    sub-command 'end'
        if (self::arg(1) === TIMEEnum::END) {
            //    id of time progress in handle ending
            if (!self::arg(2)) {
                self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print("missing a id of time progress");
                exit(1); // END app
            }
            //    id of time progress is uuid 4
            if (!UuidHelper::isValid(self::arg(2))) {
                self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print("id of time progress is invalid format");
                exit(1); // END app
            }
            //    file to store id of time progress does not exist
            if (!is_file(DirHelper::join(sys_get_temp_dir(), self::arg(2)))) {
                self::lineTagMultiple(TagEnum::VALIDATION_ERROR)->print("file to store id of time progress does not exist");
                exit(1); // END app
            }
        }
        // handle
        switch (self::arg(1)) {
            case TimeEnum::BEGIN:
                echo self::handleTimeBegin();
                break;
            case TimeEnum::END:
                echo self::handleTimeEnd(self::arg(2));
                break;
            default:
                break;
        }
    }

    /**
     * Print an id of time progress
     * @return void
     */
    private static function handleTimeBegin(): string
    {
        $idOfTimeProgress = UuidHelper::generateUuid4Native();
        file_put_contents(DirHelper::join(sys_get_temp_dir(), $idOfTimeProgress), time());
        return $idOfTimeProgress;
    }

    /**
     * Handle and print a text of time progress
     * @return void
     */
    private static function handleTimeEnd(string $idOfTimeProgress): string
    {
        $beginTime = (new DateTimeImmutable())->setTimestamp((int)file_get_contents(DirHelper::join(sys_get_temp_dir(), $idOfTimeProgress)));
        return DateHelper::getTimePeriodText($beginTime, new DateTimeImmutable());
    }
}

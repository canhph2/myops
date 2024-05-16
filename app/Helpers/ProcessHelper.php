<?php

namespace App\Helpers;

use App\Enum\ProcessEnum;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

class ProcessHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    public static function handleProcessInConsole(): void
    {
        // validate
        ValidationHelper::validateSubCommandOrParam1('sub-command-of-time', ProcessEnum::SUPPORT_SUB_COMMANDS);

        // handle
        switch (self::arg(1)) {
            case ProcessEnum::START:
                echo self::handleProcessStart();
                break;
            case ProcessEnum::FINISH:
                // do something later
                break;
            default:
                break;
        }
    }

    /**
     * @return string a 'MyOps process' id
     */
    private static function handleProcessStart(): string
    {
        return TimeHelper::handleTimeBegin();
    }

}

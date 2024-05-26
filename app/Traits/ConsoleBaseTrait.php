<?php

namespace App\Traits;

use App\Classes\Base\CustomCollection;
use App\Enum\ConsoleEnum;
use App\Helpers\ConsoleHelper;
use App\Helpers\StrHelper;

trait ConsoleBaseTrait
{
    /**
     * @param int $exitCode ConsoleEnum
     * @return void
     */
    private static function exitApp(int $exitCode = ConsoleEnum::EXIT_SUCCESS): void
    {
        exit(1); // END app
    }

    /**
     * indexes:
     * - 0 : script file
     * - 1 : arg 1
     * - 2 : arg 2
     * ...
     * @return array
     */
    private static function getPHPArgs(): array
    {
        return $_SERVER['argv'];
    }

    /**
     * indexArg:
     * - 0 : script file
     * - 1 : arg 1
     * - 2 : arg 2
     * ...
     * @param int $indexPHPArg
     * @return string|null
     */
    private static function getPHPArg(int $indexPHPArg = 0): ?string
    {
        return self::getPHPArgs()[$indexPHPArg] ?? null;
    }

    /**
     * === MyOps console trait ===
     * - this is MyOps app args organization with format: <app name> (#1) command (#2) arg1 (#3) arg 2
     * - usage:
     *   - get MyOps command command()
     *   - get MyOps arg 1  arg(1)
     *   - get MyOps arg 2  arg(2)
     *   - get MyOps arg all args()
     */

    /**
     * @return string|null
     */
    private static function command(): ?string
    {
        return self::getPHPArg(1);
    }

    /**
     * required: 1 <= $myOpsArgeIndex <= A
     * @param int $myOpsArgIndex
     * @return string|null
     */
    private static function arg(int $myOpsArgIndex = 1): ?string
    {
        return self::getPHPArg($myOpsArgIndex + 1);
    }

    /**
     * @param int $slicePosition will get myOpsArg from a slice position, .e.g $slicePosition = 1, get from 2nd myOpsArg
     * @return CustomCollection
     */
    private static function args(int $slicePosition = 0): CustomCollection
    {
        return new CustomCollection(array_slice(self::getPHPArgs(), 2 + $slicePosition));
    }

    /**
     * - a Field starts with prefix --, .e.g --field=value
     * - Get single input field will get first item
     * @param string $field
     * @return null|string|bool
     */
    private static function input(string $field)
    {
        foreach (self::args() as $arg) {
            if (StrHelper::startsWith($arg, ConsoleHelper::generateFullField($field, ''))) {
                return $arg === ConsoleHelper::generateFullField($field, '') ? true : // case input only
                    str_replace(ConsoleHelper::generateFullField($field), '', $arg); // case --field=value
            }
        }
        return null; // END
    }

    private static function inputArr(string $field): CustomCollection
    {
        $values = new CustomCollection();
        foreach (self::args() as $arg) {
            if (StrHelper::startsWith($arg, ConsoleHelper::generateFullField($field))) {
                $values->add(str_replace(ConsoleHelper::generateFullField($field), '', $arg));
            }
        }
        return $values; // END
    }

}

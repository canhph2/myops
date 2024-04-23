<?php

namespace App\Traits;

use App\Classes\Base\CustomCollection;

trait ConsoleBaseTrait
{
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
     * @return CustomCollection
     */
    private static function args(): CustomCollection
    {
        return new CustomCollection(array_slice(self::getPHPArgs(), 2));
    }

}

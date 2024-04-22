<?php

namespace App\Traits;

use App\Classes\MyOpsConsoleArguments;

trait ConsoleBaseTrait
{
    /**
     * index:
     * - 0 : script file
     * - 1 : arg 1
     * - 2 : arg 2
     * ...
     * @return array
     */
    private static function getArguments(): array
    {
        return $_SERVER['argv'];
    }

    /**
     * get argument 1
     * @return string|null
     */
    private static function getArg1(): ?string
    {
        return self::getArguments()[1] ?? null;
    }

    /**
     * get argument 2
     * @return string|null
     */
    private static function getArg2(): ?string
    {
        return self::getArguments()[2] ?? null;
    }

    /**
     * get argument 3
     * @return string|null
     */
    private static function getArg3(): ?string
    {
        return self::getArguments()[3] ?? null;
    }

    /**
     * get argument 4
     * @return string|null
     */
    private static function getArg4(): ?string
    {
        return self::getArguments()[4] ?? null;
    }

    /**
     * get argument 5
     * @return string|null
     */
    private static function getArg5(): ?string
    {
        return self::getArguments()[5] ?? null;
    }

    // === MyOps console trait ===
    private static function args(): MyOpsConsoleArguments
    {
        return new MyOpsConsoleArguments(self::getArg1(), self::getArg2(), self::getArg3(),
            self::getArg4(), self::getArg5(), array_slice(self::getArguments(), 2));
    }

}

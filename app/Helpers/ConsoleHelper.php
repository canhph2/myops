<?php

namespace App\Helpers;

use App\Enum\ConsoleEnum;
use App\Enum\IndentLevelEnum;

class ConsoleHelper
{
    /**
     * to store current indent level in this the command session
     * @var int
     */
    public static $currentIndentLevel = IndentLevelEnum::MAIN_LINE;

    public static function generateFullField(string $field, string $equalSign = '='): string
    {
        return sprintf("%s%s%s", ConsoleEnum::FIELD_PREFIX, $field, $equalSign);
    }
}

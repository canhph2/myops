<?php

namespace App\Helpers;

use App\Enum\ConsoleEnum;

class ConsoleHelper
{
    public static function generateFullField(string $field): string
    {
        return sprintf("%s%s=", ConsoleEnum::FIELD_PREFIX, $field);
    }
}

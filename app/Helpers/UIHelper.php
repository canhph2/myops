<?php

namespace App\Helpers;

use App\Enum\UIEnum;

/**
 * This is a UI Console helper
 */
class UIHelper
{
    public static function color(string $text, int $color): string
    {
        return sprintf("\033[%dm%s\033[0m", $color, $text);
    }
    public static function colorFormat(string $text, int $color, int $format): string
    {
        return sprintf("\033[%d;%dm%s\033[0m", $color, $format, $text);
    }
}

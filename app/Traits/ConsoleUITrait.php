<?php

namespace App\Traits;

use App\Classes\TextLine;

trait ConsoleUITrait
{
    /**
     * start with icon
     * @param string $icon
     * @return TextLine
     */
    public static function icon(string $icon): TextLine
    {
        return (new TextLine())->setIcon($icon);
    }
}

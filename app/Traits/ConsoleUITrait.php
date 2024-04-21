<?php

namespace App\Traits;

use App\Classes\TextLine;
use App\Enum\IndentLevelEnum;

trait ConsoleUITrait
{
    /**
     * get new instance of Text Line
     * @return TextLine
     */
    private static function lineNew(): TextLine
    {
        return (new TextLine());
    }

    /**
     * start with indent level
     * @param int $indentLevel
     * @return TextLine
     */
    private static function lineIndent(int $indentLevel = IndentLevelEnum::MAIN_LINE): TextLine
    {
        return new TextLine(null, $indentLevel);
    }

    /**
     * start with icon
     * @param string $icon
     * @return TextLine
     */
    private static function lineIcon(string $icon): TextLine
    {
        return (new TextLine())->setIcon($icon);
    }

    /**
     * start with tag
     * @param string $tag
     * @return TextLine
     */
    private static function lineTag(string $tag): TextLine
    {
        return (new TextLine())->setTag($tag);
    }

    /**
     * @param array $tags
     * @return TextLine
     */
    private static function lineTagMultiple(array $tags): TextLine
    {
        return (new TextLine())->setTagMultiple($tags);
    }

    /**
     * @param int $color
     * @return TextLine
     */
    private static function lineColor(int $color): TextLine
    {
        return (new TextLine())->setcolor($color);
    }

    /**
     * @param int $color
     * @param int $format
     * @return TextLine
     */
    private static function lineColorFormat(int $color, int $format): TextLine
    {
        return (new TextLine())->setcolor($color)->setformat($format);
    }


    // === colors ===

    /**
     * @param string $text
     * @param int $color
     * @param bool $isEndLine
     * @return string
     */
    private static function color(string $text, int $color, bool $isEndLine = false): string
    {
        return sprintf("\033[%dm%s\033[0m%s", $color, $text, $isEndLine ? PHP_EOL : '');
    }

    /**
     * @param string $text
     * @param int $color
     * @param int $format
     * @param bool $isEndLine
     * @return string
     */
    private static function colorFormat(string $text, int $color, int $format, bool $isEndLine = false): string
    {
        return sprintf("\033[%d;%dm%s\033[0m%s", $color, $format, $text, $isEndLine ? PHP_EOL : '');
    }
}

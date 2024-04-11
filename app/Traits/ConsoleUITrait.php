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
}

<?php

namespace app\Helpers;

use app\Enum\IndentLevelEnum;
use app\Enum\TagEnum;
use app\Objects\TextLine;

/**
 * This is TEXT Helper
 * - e.g. usage
 *     - TEXT::message("simple_text");
 *     - TEXT::indent(IndentLevelEnum)->icon(IconEnum)->tag(TagEnum)->message("format %s %a", params)->message("next line");
 */
class TEXT
{
    /**
     * get new instance of Text Line
     * @return TextLine
     */
    public static function new(): TextLine
    {
        return (new TextLine());
    }

    /**
     * start with indent level
     * @param int $indentLevel
     * @return TextLine
     */
    public static function indent(int $indentLevel = IndentLevelEnum::MAIN_LINE): TextLine
    {
        return new TextLine(null, $indentLevel);
    }

    /**
     * start with icon
     * @param string $icon
     * @return TextLine
     */
    public static function icon(string $icon): TextLine
    {
        return (new TextLine())->setIcon($icon);
    }

    /**
     * start with tag
     * @param string $tag
     * @return TextLine
     */
    public static function tag(string $tag): TextLine
    {
        return (new TextLine())->setTag($tag);
    }

    /**
     * @param array $tags
     * @return TextLine
     */
    public static function tagMultiple(array $tags): TextLine
    {
        return (new TextLine())->setTagMultiple($tags);
    }

    /**
     *  php _ops/lib replace-text-in-file "search text" "replace text" "file path"
     * required
     * - "search text"  (param 2)
     * - "replace text"  (param 3)
     * = "file path" ((param 4)
     * @return void
     */
    public static function replaceTextInFile(array $argv)
    {
// === validate ===
//    validate a message
        $searchText = $argv[2] ?? null;
        $replaceText = $argv[3] ?? null;
        $filePath = $argv[4] ?? null;
        if (!$searchText || is_null($replaceText) || !$filePath) {
            TEXT::tagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->message("missing a SEARCH TEXT or REPLACE TEXT or FILE PATH");
            exit(); // END
        }
        if (!is_file($filePath)) {
            self::tag(TagEnum::ERROR)->message("$filePath does not exist");
            exit(); // END
        }

// === handle ===
        $oldText = file_get_contents($filePath);
        file_put_contents($filePath, str_replace($searchText, $replaceText, $oldText));
        $newText = file_get_contents($filePath);
//    validate result
        self::new()->messageCondition($oldText !== $newText,
            "replace done with successful result", "replace done with failed result");
    }
}

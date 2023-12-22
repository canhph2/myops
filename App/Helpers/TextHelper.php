<?php

namespace App\Helpers;

class TextHelper
{
    /**
     * @param string|null $text
     * @return void
     */
    public static function message(string $text = null): void
    {
        echo sprintf("%s\n", $text);
    }

    /**
     * print a separate line
     * @return void
     */
    public static function messageSeparate(): void
    {
        self::message('===');
    }

    /**
     * print a separate line
     * @return void
     */
    public static function messageSeparateHigh(): void
    {
        self::message(PHP_EOL);
        self:: message('.');
        self::message('.');
        self::message('---------------------------------------');
        self::message('.');
        self::message('.');
        self::message(PHP_EOL);
    }

    /**
     * print a title
     * @return void
     */
    public static function messageTitle(string $title): void
    {
        self::message(sprintf("=== %s ===", $title));
    }

    /**
     * print a text to screen with tag SUCCESS
     * @param string $errorText
     * @return void
     */
    public static function messageSUCCESS(string $errorText): void
    {
        self::message(sprintf("[SUCCESS] %s", $errorText));
    }

    /**
     * print a text to screen with tag ERROR
     * @param string $errorText
     * @return void
     */
    public static function messageERROR(string $errorText): void
    {
        self::message(sprintf("[ERROR] %s", $errorText));
    }

    /**
     * @param bool $condition
     * @param string $messageSuccess
     * @param string $messageError
     * @return void
     */
    public static function messageCondition(bool $condition, string $messageSuccess, string $messageError)
    {
        $condition ? self::messageSUCCESS($messageSuccess) : self::messageERROR($messageError);
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
            TextHelper::messageERROR("[PARAMS] missing a SEARCH TEXT or REPLACE TEXT or FILE PATH");
            exit(); // END
        }
        if (!is_file($filePath)) {
            TextHelper::messageERROR("$filePath does not exist");
            exit(); // END
        }

// === handle ===
        $oldText = file_get_contents($filePath);
        file_put_contents($filePath, str_replace($searchText, $replaceText, $oldText));
        $newText = file_get_contents($filePath);
//    validate result
        if ($oldText === $newText) {
            TextHelper::messageERROR("replace done with failed result");
        } else {
            TextHelper::messageSUCCESS("replace done with successful result");
        }

    }
}

<?php

namespace App\Helpers;

use App\Classes\TextLine;
use App\Enum\TagEnum;
use App\Traits\ConsoleUITrait;

/**
 * This is TEXT Helper
 */
class TextHelper
{
    use ConsoleUITrait;

    /**
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
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->message("missing a SEARCH TEXT or REPLACE TEXT or FILE PATH");
            exit(); // END
        }
        if (!is_file($filePath)) {
            self::LineTag(TagEnum::ERROR)->message("$filePath does not exist");
            exit(); // END
        }

// === handle ===
        $oldText = file_get_contents($filePath);
        file_put_contents($filePath, str_replace($searchText, $replaceText, $oldText));
        $newText = file_get_contents($filePath);
//    validate result
        self::lineNew()->messageCondition($oldText !== $newText,
            "replace done with successful result", "replace done with failed result");
    }

    /**
     * detect some sensitive information and hide these, .e.g token, password
     *
     * @param string $line
     * @return string
     */
    public static function hideSensitiveInformation(string $line): string
    {
        // detect GitHub token
        if (StrHelper::contains($line, "https://") && StrHelper::contains($line, "@github.com")) {
            // handle hide GitHub token: show last X letter of token
            $tempArr = explode("https://", $line);
            $tempArr2 = explode("@github.com", $tempArr[1]);
            $token = $tempArr2[0];
            $hiddenToken = "****" . substr($token, -3);
            $line = str_replace($token, $hiddenToken, $line);
        }
        return $line;
    }
}

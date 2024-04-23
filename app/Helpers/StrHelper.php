<?php


namespace App\Helpers;

use App\Enum\TagEnum;
use App\Traits\ConsoleBaseTrait;
use App\Traits\ConsoleUITrait;

/**
 * this is a simple STRing helper for PHP < 8.1
 */
class StrHelper
{
    use ConsoleBaseTrait, ConsoleUITrait;

    /**
     * @param string $toCheck
     * @param string $search
     * @return bool
     */
    public static function contains(string $toCheck, string $search): bool
    {
        return strpos($toCheck, $search) !== false;
    }

    /**
     * @param string $toCheck
     * @param string $search
     * @return bool
     */
    public static function startWith(string $toCheck, string $search): bool
    {
        return strpos($toCheck, $search) === 0;
    }

    /**
     * @param string $toCheck
     * @param string $search
     * @return bool
     */
    public static function endWith(string $toCheck, string $search): bool
    {
        $length = strlen($search);
        if ($length === 0) {
            return false; // Empty needle always matches
        }
        return substr($toCheck, -$length) === $search;
    }

    // === text processing ===

    /**
     * required
     * - "search text"  (param 2)
     * - "replace text"  (param 3)
     * = "file path" ((param 4)
     * @return void
     */
    public static function replaceTextInFile()
    {
// === validate ===
//    validate a message
        $searchText = self::arg(1);
        $replaceText = self::arg(2);
        $filePath = self::arg(3);
        if (!$searchText || is_null($replaceText) || !$filePath) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->print("missing a SEARCH TEXT or REPLACE TEXT or FILE PATH");
            exit(); // END
        }
        if (!is_file($filePath)) {
            self::LineTag(TagEnum::ERROR)->print("$filePath does not exist");
            exit(); // END
        }

// === handle ===
        $oldText = file_get_contents($filePath);
        file_put_contents($filePath, str_replace($searchText, $replaceText, $oldText));
        $newText = file_get_contents($filePath);
//    validate result
        self::lineNew()->printCondition($oldText !== $newText,
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

    /**
     * @param string $filePath
     * @param string $searchText
     * @return array
     */
    public static function findLinesContainsTextInFile(string $filePath, string $searchText): array
    {
        return array_filter(explode(PHP_EOL, file_get_contents($filePath)), function ($line) use ($searchText) {
            return self::contains($line, $searchText);
        });

    }
}

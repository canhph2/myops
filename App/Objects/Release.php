<?php

namespace App\Objects;

use App\Helpers\DEVHelper;

class Release
{
    /**
     * @var array
     * to release
     */
    const FILES_LIST = [
        // === Enum ===
        'App/Enum/CommandEnum.php',
        // === Helper ===
        'App/Helpers/DEVHelper.php',
        // === Objects ===
        'App/Objects/Release.php',
        // always on bottom
        'App/app.php',
    ];

    const RELEASE_PATH = '_ops/lib';

    public function __construct()
    {

    }

    public function handle()
    {
        echo DEVHelper::message("init ops/lib file\n", __CLASS__, __FUNCTION__);
        file_put_contents(self::RELEASE_PATH, "#!/usr/bin/env php\n<?php\n// === OPS SHARED LIBRARY (PHP) ===\n"); // init file
        $this->handleLibrariesClass();
        $this->handleAppClass();
        echo DEVHelper::message("DONE\n", __CLASS__, __FUNCTION__);
    }

    /**
     * remove tab <?php
     * remove namespace
     * remove some unused elements
     * @param string $classPath
     * @return string
     */
    private function handlePHPClassContent(string $classPath): string
    {
        // remove php tag
        $classContent = str_replace('<?php', '', trim(file_get_contents($classPath)));
        // remove unused elements
        $lines = explode("\n", $classContent);
        $modifiedLines = [];
        foreach ($lines as $line) {
            // remove 'namespace'
            if (strpos($line, "namespace ") === 0) {
                $line = "// [REMOVED] " . $line;
            }
            // remove 'use'
            if (strpos($line, "use ") === 0) {
                $line = "// [REMOVED] " . $line;
            }
            $modifiedLines[] = $line;
        }
        return implode("\n", $modifiedLines);
    }

    /**
     * @return void
     */
    private function handleAppClass(): void
    {
        $appClassContent = $this->handlePHPClassContent(self::FILES_LIST[count(self::FILES_LIST) - 1]);
        $appClassContentClassOnly = sprintf("class App%s", explode('class App', $appClassContent)[1]);
        file_put_contents(
            self::RELEASE_PATH,
            sprintf("\n// === Generated app class ===\n\n%s\n\n// === end Generated app class ===\n\n", $appClassContentClassOnly),
            FILE_APPEND
        ); // init file
    }

    /**
     * @return void
     */
    private function handleLibrariesClass(): void
    {
        $librariesClassesContent = "";
        for ($i = 0; $i < count(self::FILES_LIST) - 1; $i++) {
            $librariesClassesContent .= $this->handlePHPClassContent(self::FILES_LIST[$i]);
        }
        file_put_contents(
            self::RELEASE_PATH,
            sprintf("\n// === Generated libraries classes ===\n\n%s\n\n// === end Generated libraries classes ===\n\n", $librariesClassesContent),
            FILE_APPEND
        ); // init file
    }
}

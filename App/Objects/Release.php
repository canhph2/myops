<?php

namespace App\Objects;

use App\App;
use App\Enum\GitHubEnum;
use App\Helpers\AppHelper;
use App\Helpers\DEVHelper;
use App\Helpers\DirHelper;
use App\Helpers\TextHelper;
use DateTime;

class Release
{
    /**
     * @var array
     * to release
     */
    const FILES_LIST = [
        // === Enum ===
        'App/Enum/CommandEnum.php',
        'App/Enum/GitHubEnum.php',
        // === Helper ===
        'App/Helpers/DEVHelper.php',
        'App/Helpers/DirHelper.php',
        'App/Helpers/OpsHelper.php',
        'App/Helpers/TextHelper.php',
        'App/Helpers/GitHubHelper.php',
        'App/Helpers/ServicesHelper.php',
        'App/Helpers/AWSHelper.php',
        'App/Helpers/AppHelper.php',
        // === Objects ===
        'App/Objects/Release.php',
        'App/Objects/Process.php',
        'App/Objects/Version.php',
        // always on bottom
        'App/app.php',
    ];

    const RELEASE_PATH = '_ops/lib';

    public function __construct()
    {

    }

    /**
     *  null: validate OK
     *  string: error message
     * @return string|null
     */
    private function validate(): ?string
    {
        switch (basename(DirHelper::getScriptDir())) {
            case 'App':
                return null;
            case '_ops':
                return "[ERROR] in release directory \ another project, stop release job\n";
            default:
                return "[ERROR] unknown error\n";
        }
    }

    public function handle(): void
    {
        if ($this->validate()) {
            echo DEVHelper::message($this->validate(), __CLASS__, __FUNCTION__);
            return; // END
        }
        // handle
        //    increase app version
        AppHelper::increaseVersion();
        //    generate files
        echo DEVHelper::message("init ops/lib file\n", __CLASS__, __FUNCTION__);
        file_put_contents(self::RELEASE_PATH, sprintf("#!/usr/bin/env php\n<?php\n// === %s ===\n", App::versionNew())); // init file
        $this->handleLibrariesClass();
        $this->handleAppClass();
        echo DEVHelper::message("DONE\n", __CLASS__, __FUNCTION__);
        //    push new release to GitHub
        (new Process("PUSH NEW RELEASE TO GITHUB", DirHelper::getWorkingDir(), [
            GitHubEnum::ADD_ALL_FILES_COMMAND,
            sprintf("git commit -m 'release %s on %s UTC'", App::versionNew(), (new DateTime())->format('Y-m-d H:i:s')),
            GitHubEnum::PUSH_COMMAND,
        ]))->execMultiInWorkDir()->printOutput();
        //
        TextHelper::messageSeparate();
        TextHelper::messageSUCCESS(sprintf("Release successful %s", App::versionNew()));
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
    private
    function handleAppClass(): void
    {
        $appClassContent = $this->handlePHPClassContent(self::FILES_LIST[count(self::FILES_LIST) - 1]);
        $appClassContentClassOnly = sprintf("class App%s", explode('class App', $appClassContent)[1]);
        // handle shell data
        $appClassContentClassOnly = str_replace(
            "const SHELL_DATA_BASE_64 = '';",
            sprintf("const SHELL_DATA_BASE_64 = '%s';", base64_encode(file_get_contents("App/_shell_/handle-env-ops.sh"))),
            $appClassContentClassOnly
        );
        //
        file_put_contents(
            self::RELEASE_PATH,
            sprintf("\n// === Generated app class ===\n\n%s\n\n// === end Generated app class ===\n\n", $appClassContentClassOnly),
            FILE_APPEND
        ); // init file
    }

    /**
     * @return void
     */
    private
    function handleLibrariesClass(): void
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

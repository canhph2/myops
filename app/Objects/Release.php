<?php

namespace app\Objects;

use app\app;
use app\Enum\GitHubEnum;
use app\Helpers\AppHelper;
use app\Helpers\DEVHelper;
use app\Helpers\DirHelper;
use app\Helpers\TextHelper;
use DateTime;

class Release
{
    /**
     * @var array
     * to release
     */
    const FILES_LIST = [
        // === Enum ===
        'app/Enum/CommandEnum.php',
        'app/Enum/GitHubEnum.php',
        // === Helper ===
        'app/Helpers/DEVHelper.php',
        'app/Helpers/DirHelper.php',
        'app/Helpers/OpsHelper.php',
        'app/Helpers/TextHelper.php',
        'app/Helpers/GitHubHelper.php',
        'app/Helpers/ServicesHelper.php',
        'app/Helpers/AWSHelper.php',
        'app/Helpers/AppHelper.php',
        'app/Helpers/STR.php',
        // === Objects ===
        'app/Objects/Release.php',
        'app/Objects/Process.php',
        'app/Objects/Version.php',
        // always on bottom
        'app/app',
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
            case 'app':
                return null;
            case '_ops':
                return "[ERROR] in release directory \ another project, stop release job\n";
            default:
                return "[ERROR] unknown error\n";
        }
    }

    public function handle(array $argv): void
    {
        // validate
        if ($this->validate()) {
            echo DEVHelper::message($this->validate(), __CLASS__, __FUNCTION__);
            return; // END
        }
        //    validate version part
        $part = $argv[2] ?? Version::PATCH; // default, empty = patch
        if (!in_array($part, Version::PARTS)) {
            TextHelper::messageERROR(sprintf("invalid part of version, should be: %s", join(', ', Version::PARTS)));
            return; // END
        }
        // handle
        //    increase app version
        $newVersion = AppHelper::increaseVersion($part);
        //    generate files
        echo DEVHelper::message("init ops/lib file\n", __CLASS__, __FUNCTION__);
        file_put_contents(self::RELEASE_PATH, sprintf("#!/usr/bin/env php\n<?php\n// === %s ===\n", app::version($newVersion))); // init file
        $this->handleLibrariesClass();
        $this->handleAppClass();
        echo DEVHelper::message("DONE\n", __CLASS__, __FUNCTION__);
        //    push new release to GitHub
        (new Process("PUSH NEW RELEASE TO GITHUB", DirHelper::getWorkingDir(), [
            GitHubEnum::ADD_ALL_FILES_COMMAND,
            sprintf("git commit -m 'release %s on %s UTC'", app::version($newVersion), (new DateTime())->format('Y-m-d H:i:s')),
            GitHubEnum::PUSH_COMMAND,
        ]))->execMultiInWorkDir()->printOutput();
        //
        TextHelper::messageSeparate();
        TextHelper::messageSUCCESS(sprintf("Release successful %s", app::version($newVersion)));
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
        $classContent = str_replace('#!/usr/bin/env php', '', trim(file_get_contents($classPath)));
        $classContent = str_replace('<?php', '', $classContent);
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
        $appClassContentClassOnly = sprintf("class app%s", explode('class app', $appClassContent)[1]);
        // handle shell data
        $appClassContentClassOnly = str_replace(
            "const SHELL_DATA_BASE_64 = '';",
            sprintf("const SHELL_DATA_BASE_64 = '%s';", base64_encode(app::getShellData())),
            $appClassContentClassOnly
        );
        // handle ELB template
        $appClassContentClassOnly = str_replace(
            "const ELB_TEMPLATE_BASE_64 = '';",
            sprintf("const ELB_TEMPLATE_BASE_64 = '%s';", base64_encode(json_encode(app::getELBTemplate()))),
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

<?php

namespace App\Classes;

use App\Enum\CommandEnum;
use App\Enum\DockerEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\PostWorkEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Enum\ValidationTypeEnum;
use App\Helpers\AppHelper;
use App\Helpers\AWSHelper;
use App\Helpers\DirHelper;
use App\Helpers\DockerHelper;
use App\Helpers\GitHubHelper;
use App\Helpers\OPSHelper;
use App\Helpers\StrHelper;
use App\OpsApp;
use App\Services\SlackService;
use App\Traits\ConsoleUITrait;
use DateTime;

class Release
{
    use ConsoleUITrait;

    /**
     * @return array
     * to release
     */
    public static function GET_FILES_LIST(): array
    {
        return [
            // === Classes ===
            DirHelper::getClassPathAndFileName(Release::class),
            DirHelper::getClassPathAndFileName(Process::class),
            DirHelper::getClassPathAndFileName(Version::class),
            DirHelper::getClassPathAndFileName(DockerImage::class),
            DirHelper::getClassPathAndFileName(TextLine::class),
            DirHelper::getClassPathAndFileName(GitHubRepositoryInfo::class),
            DirHelper::getClassPathAndFileName(Duration::class),
            // === Enum ===
            DirHelper::getClassPathAndFileName(CommandEnum::class),
            DirHelper::getClassPathAndFileName(GitHubEnum::class),
            DirHelper::getClassPathAndFileName(IndentLevelEnum::class),
            DirHelper::getClassPathAndFileName(IconEnum::class),
            DirHelper::getClassPathAndFileName(TagEnum::class),
            DirHelper::getClassPathAndFileName(UIEnum::class),
            DirHelper::getClassPathAndFileName(DockerEnum::class),
            DirHelper::getClassPathAndFileName(ValidationTypeEnum::class),
            DirHelper::getClassPathAndFileName(PostWorkEnum::class),
            // === Helper ===
            DirHelper::getClassPathAndFileName(DirHelper::class),
            DirHelper::getClassPathAndFileName(OPSHelper::class),
            DirHelper::getClassPathAndFileName(GitHubHelper::class),
            DirHelper::getClassPathAndFileName(AWSHelper::class),
            DirHelper::getClassPathAndFileName(AppHelper::class),
            DirHelper::getClassPathAndFileName(DockerHelper::class),
            DirHelper::getClassPathAndFileName(StrHelper::class),
            // === Services ===
            DirHelper::getClassPathAndFileName(SlackService::class),
            // === Traits ===
            DirHelper::getClassPathAndFileName(ConsoleUITrait::class),
            // App file always on bottom
            DirHelper::getClassPathAndFileName(OpsApp::class),
        ];
    }

    const RELEASE_PATH = '.release/OpsApp.php';

    public function __construct()
    {

    }

    /**
     *  null: validate OK
     *  string: error message
     * @return bool
     */
    private function validate(): bool
    {
        switch (basename(DirHelper::getScriptDir())) {
            case 'app':
                return true;
            case '.release':
            case basename(DirHelper::getHomeDir()):
                self::LineTag(TagEnum::ERROR)->print("release in directory / another project, stop release job");
                return false;
            default:
                self::LineTag(TagEnum::ERROR)->print("unknown error");
                return false;
        }
    }

    public function handle(array $argv): void
    {
        // validate
        if (!$this->validate()) {
            return; // END
        }
        //    validate version part
        $part = $argv[2] ?? Version::PATCH; // default, empty = patch
        if (!in_array($part, Version::PARTS)) {
            self::LineTag(TagEnum::ERROR)->print("invalid part of version, should be: %s", join(', ', Version::PARTS));
            return; // END
        }
        // handle
        self::LineNew()->printTitle("release");
        //    increase app version
        $newVersion = AppHelper::increaseVersion($part);
        //    combine files
        self::LineTagMultiple([__CLASS__, __FUNCTION__])->print("combine files");
        file_put_contents(self::RELEASE_PATH, sprintf("<?php\n// === %s ===\n", OpsApp::version($newVersion)));
        $this->handleLibrariesClass();
        $this->handleAppClass();
        //
        self::LineTagMultiple([__CLASS__, __FUNCTION__])->print("DONE");
        //    push new release to GitHub
        (new Process("PUSH NEW RELEASE TO GITHUB", DirHelper::getWorkingDir(), [
            GitHubEnum::ADD_ALL_FILES_COMMAND,
            sprintf("git commit -m 'release %s on %s UTC'", OpsApp::version($newVersion), (new DateTime())->format('Y-m-d H:i:s')),
            GitHubEnum::PUSH_COMMAND,
        ]))->execMultiInWorkDir()->printOutput();
        //
        self::LineNew()->printSeparatorLine()
            ->setTag(TagEnum::SUCCESS)->print("Release successful %s", OpsApp::version($newVersion));
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
        $appClassContent = $this->handlePHPClassContent(self::GET_FILES_LIST()[count(self::GET_FILES_LIST()) - 1]);
        $appClassContentClassOnly = sprintf("class OpsApp%s", explode('class OpsApp', $appClassContent)[1]);
        // handle shell data
        $appClassContentClassOnly = str_replace(
            "const SHELL_DATA_BASE_64 = '';",
            sprintf("const SHELL_DATA_BASE_64 = '%s';", base64_encode(OpsApp::getShellData())),
            $appClassContentClassOnly
        );
        // handle ELB template
        $appClassContentClassOnly = str_replace(
            "const ELB_TEMPLATE_BASE_64 = '';",
            sprintf("const ELB_TEMPLATE_BASE_64 = '%s';", base64_encode(json_encode(OpsApp::getELBTemplate()))),
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
        for ($i = 0; $i < count(self::GET_FILES_LIST()) - 1; $i++) {
            $librariesClassesContent .= $this->handlePHPClassContent(self::GET_FILES_LIST()[$i]);
        }
        file_put_contents(
            self::RELEASE_PATH,
            sprintf("\n// === Generated libraries classes ===\n\n%s\n\n// === end Generated libraries classes ===\n\n", $librariesClassesContent),
            FILE_APPEND
        ); // init file
    }
}

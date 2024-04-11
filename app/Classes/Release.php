<?php

namespace app\Classes;

use app\app;
use app\Enum\CommandEnum;
use app\Enum\DockerEnum;
use app\Enum\GitHubEnum;
use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\PostWorkEnum;
use app\Enum\TagEnum;
use app\Enum\UIEnum;
use app\Enum\ValidationTypeEnum;
use app\Helpers\AppHelper;
use app\Helpers\AWSHelper;
use app\Helpers\DirHelper;
use app\Helpers\DockerHelper;
use app\Helpers\GitHubHelper;
use app\Helpers\OPSHelper;
use app\Helpers\StrHelper;
use app\Helpers\TextHelper;
use app\Helpers\UIHelper;
use app\Services\SlackService;
use DateTime;

class Release
{
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
            DirHelper::getClassPathAndFileName(TextHelper::class),
            DirHelper::getClassPathAndFileName(GitHubHelper::class),
            DirHelper::getClassPathAndFileName(AWSHelper::class),
            DirHelper::getClassPathAndFileName(AppHelper::class),
            DirHelper::getClassPathAndFileName(DockerHelper::class),
            DirHelper::getClassPathAndFileName(StrHelper::class),
            DirHelper::getClassPathAndFileName(UIHelper::class),
            // === Services ===
            DirHelper::getClassPathAndFileName(SlackService::class),
            // always on bottom
            'app/app',
        ];
    }

    const RELEASE_PATH = '_ops/lib';

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
            case '_ops':
                TextHelper::tag(TagEnum::ERROR)->message("release in directory / another project, stop release job");
                return false;
            default:
                TextHelper::tag(TagEnum::ERROR)->message("unknown error");
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
            TextHelper::tag(TagEnum::ERROR)->message("invalid part of version, should be: %s", join(', ', Version::PARTS));
            return; // END
        }
        // handle
        TextHelper::new()->messageTitle("release");
        //    increase app version
        $newVersion = AppHelper::increaseVersion($part);
        //    generate files
        TextHelper::tagMultiple([__CLASS__, __FUNCTION__])->message("init ops/lib file");
        file_put_contents(self::RELEASE_PATH, sprintf("#!/usr/bin/env php\n<?php\n// === %s ===\n", app::version($newVersion))); // init file
        $this->handleLibrariesClass();
        $this->handleAppClass();
        TextHelper::tagMultiple([__CLASS__, __FUNCTION__])->message("DONE");
        //    push new release to GitHub
        (new Process("PUSH NEW RELEASE TO GITHUB", DirHelper::getWorkingDir(), [
            GitHubEnum::ADD_ALL_FILES_COMMAND,
            sprintf("git commit -m 'release %s on %s UTC'", app::version($newVersion), (new DateTime())->format('Y-m-d H:i:s')),
            GitHubEnum::PUSH_COMMAND,
        ]))->execMultiInWorkDir()->printOutput();
        //
        TextHelper::new()->messageSeparate()
            ->setTag(TagEnum::SUCCESS)->message("Release successful %s", app::version($newVersion));
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
        $appClassContent = $this->handlePHPClassContent(self::GET_FILES_LIST()[count(self::GET_FILES_LIST()) - 1]);
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

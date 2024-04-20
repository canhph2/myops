<?php
// === [34;1mOPS APP (PHP) v3.2.17[0m ===

// === Generated libraries classes ===



// [REMOVED] namespace App\Classes;

// [REMOVED] use App\Enum\CommandEnum;
// [REMOVED] use App\Enum\DockerEnum;
// [REMOVED] use App\Enum\GitHubEnum;
// [REMOVED] use App\Enum\IconEnum;
// [REMOVED] use App\Enum\IndentLevelEnum;
// [REMOVED] use App\Enum\PostWorkEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Enum\UIEnum;
// [REMOVED] use App\Enum\ValidationTypeEnum;
// [REMOVED] use App\Helpers\AppHelper;
// [REMOVED] use App\Helpers\AWSHelper;
// [REMOVED] use App\Helpers\DirHelper;
// [REMOVED] use App\Helpers\DockerHelper;
// [REMOVED] use App\Helpers\GitHubHelper;
// [REMOVED] use App\Helpers\OPSHelper;
// [REMOVED] use App\Helpers\StrHelper;
// [REMOVED] use App\OpsApp;
// [REMOVED] use App\Services\SlackService;
// [REMOVED] use App\Traits\ConsoleUITrait;
// [REMOVED] use DateTime;

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
        file_put_contents(self::RELEASE_PATH, sprintf("\n// === %s ===\n", OpsApp::version($newVersion)));
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
     * remove tab 
     * remove namespace
     * remove some unused elements
     * @param string $classPath
     * @return string
     */
    private function handlePHPClassContent(string $classPath): string
    {
        // remove php tag
        $classContent = str_replace('', '', trim(file_get_contents($classPath)));
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

// [REMOVED] namespace App\Classes;

// [REMOVED] use App\Enum\GitHubEnum;
// [REMOVED] use App\Enum\IconEnum;
// [REMOVED] use App\Enum\IndentLevelEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Helpers\AWSHelper;
// [REMOVED] use App\Helpers\DirHelper;
// [REMOVED] use App\Helpers\OPSHelper;
// [REMOVED] use App\Helpers\StrHelper;
// [REMOVED] use App\OpsApp;
// [REMOVED] use App\Traits\ConsoleUITrait;

class Process
{
    use ConsoleUITrait;

    /** @var string|null */
    private $workName;

    /** @var string|null */
    private $workDir;

    /** @var array|null */
    private $commands;

    /** @var array|null */
    private $output;

    /** @var int */
    private $outputParentIndentLevel = IndentLevelEnum::MAIN_LINE;

    /** @var bool */
    private $isExitOnError;

    /**
     * @param string|null $workName
     * @param string|null $workDir
     * @param array|null $commands
     */
    public function __construct(
        string $workName = null,
        string $workDir = null,
        array  $commands = null
    )
    {
        $this->workName = $workName;
        $this->workDir = $workDir;
        $this->commands = $commands;
        // default
        $this->isExitOnError = true; // default
    }

    /**
     * @return string|null
     */
    public function getWorkName(): ?string
    {
        return $this->workName;
    }

    /**
     * @param string|null $workName
     * @return Process
     */
    public function setWorkName(?string $workName): Process
    {
        $this->workName = $workName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWorkDir(): ?string
    {
        return $this->workDir;
    }

    /**
     * @param string|null $workDir
     * @return Process
     */
    public function setWorkDir(?string $workDir): Process
    {
        $this->workDir = $workDir;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getCommands(): ?array
    {
        return $this->commands;
    }

    /**
     * @param array|null $commands
     * @return Process
     */
    public function setCommands(?array $commands): Process
    {
        $this->commands = $commands;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getOutput(): ?array
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getOutputStrAll(): string
    {
        return join(PHP_EOL, $this->output);
    }

    /**
     * @param array|null $output
     * @return Process
     */
    public function setOutput(?array $output): Process
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return int
     */
    public function getOutputParentIndentLevel(): int
    {
        return $this->outputParentIndentLevel;
    }

    /**
     * @param int $outputParentIndentLevel
     * @return Process
     */
    public function setOutputParentIndentLevel(int $outputParentIndentLevel): Process
    {
        $this->outputParentIndentLevel = $outputParentIndentLevel;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExitOnError(): bool
    {
        return $this->isExitOnError;
    }

    /**
     * @param bool $isExitOnError
     * @return Process
     */
    public function setIsExitOnError(bool $isExitOnError): Process
    {
        $this->isExitOnError = $isExitOnError;
        return $this;
    }


    // === UTILS ZONE ===

    public function execMulti(): Process
    {
        // === validate ===
        //    dangerous command
        foreach ($this->commands as $command) {
            $command = trim($command);
            if (in_array(str_replace("  ", " ", trim($command)), [
                "rm -rf",
                "rm -rf ''",
                "rm -rf ' '",
                "rm -rf \"\"",
                "rm -rf \" \"",
                "rm -rf /",
                "rm -rf '/'",
                "rm -rf \"/\"",
                sprintf("rm -rf %s", DirHelper::getHomeDir()),
                sprintf("rm -rf '%s'", DirHelper::getHomeDir()),
                sprintf("rm -rf \"%s\"", DirHelper::getHomeDir()),
                sprintf("rm -rf %s/", DirHelper::getHomeDir()),
                sprintf("rm -rf '%s/'", DirHelper::getHomeDir()),
                sprintf("rm -rf \"%s/\"", DirHelper::getHomeDir()),
            ])) {
                self::LineTag(TagEnum::ERROR)->print("detect dangerous command: $command  , exit app");
                exit(1); // END
            }
        }
        // === handle ===
        //    handle alias when run alias 'ops-app' in Process
        $isContainsAlias = false;
        foreach ($this->commands as $command) {
            if (StrHelper::startWith($command, OpsApp::APP_MAIN_COMMAND)) {
                $isContainsAlias = true;
                break;
            }
        }
        if ($isContainsAlias) {
            // load env into PHP
            OPSHelper::parseEnoughDataForSync(AWSHelper::loadOpsEnvAndHandleMore());
            $EngagePlusCachesRepositoryOpsAppReleasePath = sprintf("%s/ops-app/%s", getenv('ENGAGEPLUS_CACHES_DIR'), Release::RELEASE_PATH);
            // replace alias
            for ($i = 0; $i < count($this->commands); $i++) {
                if (StrHelper::startWith($this->commands[$i], OpsApp::APP_MAIN_COMMAND)) {
                    $this->commands[$i] = "php '$EngagePlusCachesRepositoryOpsAppReleasePath'" . substr($this->commands[$i], strlen(OpsApp::APP_MAIN_COMMAND));
                }
            }
        }
        //
        if ($this->commands) {
            $resultCode = null;
            exec(join(';', $this->commands), $this->output, $exitCode);
            if ($exitCode && $this->isExitOnError) {
                $this->printOutput();
                self::LineTag(TagEnum::ERROR)->print("detect execute shell command failed, exit app | exit code = $exitCode");
                exit($exitCode); // END app
            }
        }
        //
        return $this;
    }

    public function execMultiInWorkDir(bool $skipCheckDir = false): Process
    {
        // dir commands
        $arrDirCommands[] = sprintf("cd '%s'", $this->workDir); // cd
        if (!$skipCheckDir) {
            $arrDirCommands[] = GitHubEnum::GET_REPOSITORY_DIR_COMMAND; // check dir
        }
        $this->commands = array_merge($arrDirCommands, $this->commands);
        $this->execMulti();
        //
        return $this;
    }

    /**
     * will execMultiInWorkDir | skip check dir | return output string
     * @return string|null
     */
    public function execMultiInWorkDirAndGetOutputStr(): ?string
    {
        return $this->execMultiInWorkDir(true)->getOutputStrAll();
    }

    public function printOutput(): Process
    {
        self::LineIndent($this->getOutputParentIndentLevel())->setTag(TagEnum::WORK)->print($this->workName);
        self::LineIndent($this->getOutputParentIndentLevel())->setIcon(IconEnum::HYPHEN)->print("Commands:");
        if ($this->commands) {
            foreach ($this->commands as $command) {
                self::LineIndent($this->getOutputParentIndentLevel() + IndentLevelEnum::ITEM_LINE)
                    ->setIcon(IconEnum::CHEVRON_RIGHT)->print(StrHelper::hideSensitiveInformation($command));
            }
        }
        self::LineIndent($this->getOutputParentIndentLevel())->setIcon(IconEnum::HYPHEN)->print("Output:");
        if ($this->output) {
            foreach ($this->output as $outputLine) {
                self::LineIndent($this->getOutputParentIndentLevel() + IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::PLUS)->print(StrHelper::hideSensitiveInformation($outputLine));
            }
        }
        //
        return $this;
    }


    // === END UTILS ZONE ===

}

// [REMOVED] namespace App\Classes;

// [REMOVED] use InvalidArgumentException;

/**
 * by BARD AI
 */
class Version
{

    const MAJOR = 'major';
    const MINOR = 'minor';
    const PATCH = 'patch';
    const BUILD = 'build';

    const PARTS = [self::MAJOR, self::MINOR, self::PATCH, self::BUILD];

    /** @var int */
    private $major;
    /** @var int */
    private $minor;
    /** @var int */
    private $patch;
    /** @var int */
    private $build;

    public function __construct($major, $minor, $patch, $build = null)
    {
        $this->major = (int)$major;
        $this->minor = (int)$minor;
        $this->patch = (int)$patch;
        $this->build = (int)($build ?: 0);
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function setMajor(int $major): void
    {
        $this->major = $major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function setMinor(int $minor): void
    {
        $this->minor = $minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function setPatch(int $patch): void
    {
        $this->patch = $patch;
    }

    public function getBuild(): int
    {
        return $this->build;
    }

    public function setBuild(int $build): void
    {
        $this->build = $build;
    }

    public static function parse(string $versionStr): Version
    {
        $versionData = explode('.', trim($versionStr));
        return new Version($versionData[0], $versionData[1], $versionData[2]);
    }


    /**
     * return 1.0.0.0  (major.minor.patch.build)
     * @return string
     */
    public function toStringFull()
    {
        return $this->major . '.' . $this->minor . '.' . $this->patch . '.' . $this->build;
    }

    /**
     * return 1.0.0  (major.minor.patch)
     * @return string
     */
    public function toString(): string
    {
        return $this->major . '.' . $this->minor . '.' . $this->patch;
    }

    public function compare($otherVersion): int
    {
        if (!$otherVersion instanceof self) {
            throw new InvalidArgumentException('Argument must be an instance of Version');
        }

        if ($this->major < $otherVersion->major) {
            return -1;
        }

        if ($this->major > $otherVersion->major) {
            return 1;
        }

        if ($this->minor < $otherVersion->minor) {
            return -1;
        }

        if ($this->minor > $otherVersion->minor) {
            return 1;
        }

        if ($this->patch < $otherVersion->patch) {
            return -1;
        }

        if ($this->patch > $otherVersion->patch) {
            return 1;
        }

        if ($this->build < $otherVersion->build) {
            return -1;
        }

        if ($this->build > $otherVersion->build) {
            return 1;
        }

        return 0;
    }

    public function isCompatible($otherVersion)
    {
        if (!$otherVersion instanceof self) {
            throw new InvalidArgumentException('Argument must be an instance of Version');
        }

        return $this->compare($otherVersion) >= 0;
    }

    public function bump($part = self::PATCH): Version
    {
        if (!in_array($part, self::PARTS)) {
            throw new InvalidArgumentException('Invalid version part');
        }
        if ($part === self::MAJOR) {
            $this->major++;
            // reset minor, patch, build
            $this->minor = 0;
            $this->patch = 0;
            $this->build = 0;
        }
        if ($part === self::MINOR) {
            $this->minor++;
            // reset patch, build
            $this->patch = 0;
            $this->build = 0;
        }
        if ($part === self::PATCH) {
            $this->patch++;
            // reset build
            $this->build = 0;
        }
        if ($part === self::BUILD) {
            $this->build++;
        }
        return $this;
    }
}

// [REMOVED] namespace App\Classes;

class DockerImage
{
    public const NONE = '<none>';

    /** @var string|null */
    private $repository;

    /** @var string|null */
    private $tag;

    /** @var string|null */
    private $id;

    /** @var string|null */
    private $createdAt;

    /** @var string|null */
    private $createdSince;

    /** @var string|null */
    private $size;

    /**
     * @param string|null $repository
     * @param string|null $tag
     * @param string|null $id
     * @param string|null $createdAt
     * @param string|null $createdSince
     * @param string|null $size
     */
    public function __construct(?string $repository, ?string $tag, ?string $id, ?string $createdAt, ?string $createdSince, ?string $size)
    {
        $this->repository = $repository;
        $this->tag = $tag;
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->createdSince = $createdSince;
        $this->size = $size;
    }

    /**
     * @return string|null
     */
    public function getRepository(): ?string
    {
        return $this->repository;
    }

    /**
     * @param string|null $repository
     * @return DockerImage
     */
    public function setRepository(?string $repository): DockerImage
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @param string|null $tag
     * @return DockerImage
     */
    public function setTag(?string $tag): DockerImage
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     * @return DockerImage
     */
    public function setId(?string $id): DockerImage
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @param string|null $createdAt
     * @return DockerImage
     */
    public function setCreatedAt(?string $createdAt): DockerImage
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatedSince(): ?string
    {
        return $this->createdSince;
    }

    /**
     * @param string|null $createdSince
     * @return DockerImage
     */
    public function setCreatedSince(?string $createdSince): DockerImage
    {
        $this->createdSince = $createdSince;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * @param string|null $size
     * @return DockerImage
     */
    public function setSize(?string $size): DockerImage
    {
        $this->size = $size;
        return $this;
    }

    // === others function ===

    /**
     * dangling image / <none> image
     * @return bool
     */
    public function isDanglingImage(): bool
    {
        return $this->repository === self::NONE || $this->tag === self::NONE;
    }
}

// [REMOVED] namespace App\Classes;

// [REMOVED] use App\Enum\IndentLevelEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Enum\UIEnum;
// [REMOVED] use App\Traits\ConsoleUITrait;

class TextLine
{
    use ConsoleUITrait;

    /** @var string */
    private $text;

    /** @var int */
    private $indentLevel;

    /** @var string */
    private $icon;

    /** @var string */
    private $tag;

    /** @var int */
    private $color;

    /** @var int */
    private $format;

    /**
     * @param string|null $text
     * @param int $indentLevel
     * @param string|null $icon
     * @param string|null $tag
     */
    public function __construct(
        string $text = null, int $indentLevel = IndentLevelEnum::MAIN_LINE,
        string $icon = null, string $tag = null
    )
    {
        $this->text = $text;
        $this->indentLevel = $indentLevel;
        $this->icon = $icon;
        $this->tag = $tag;
        // UI
        $this->resetColorFormat();
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     * @return TextLine
     */
    public function setText(?string $text): TextLine
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return int
     */
    public function getIndentLevel(): int
    {
        return $this->indentLevel;
    }

    /**
     * @param int $indentLevel
     * @return TextLine
     */
    public function setIndentLevel(int $indentLevel): TextLine
    {
        $this->indentLevel = $indentLevel;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     * @return TextLine
     */
    public function setIcon(?string $icon): TextLine
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @param string|null $tag
     * @return TextLine
     */
    public function setTag(?string $tag): TextLine
    {
        $this->tag = $tag;
        // format
        if ($this->tag === TagEnum::SUCCESS) {
            $this->color = UIEnum::COLOR_GREEN;
        } else if ($this->tag === TagEnum::ERROR) {
            $this->color = UIEnum::COLOR_RED;
        }
        //
        return $this;
    }

    public function setTagMultiple(array $tags): TextLine
    {
        $this->tag = join(' | ', $tags);
        // format
        if (in_array(TagEnum::SUCCESS, $tags)) {
            $this->color = UIEnum::COLOR_GREEN;
        } else if (in_array(TagEnum::ERROR, $tags)) {
            $this->color = UIEnum::COLOR_RED;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getColor(): int
    {
        return $this->color;
    }

    /**
     * @param int $color
     * @return TextLine
     */
    public function setColor(int $color): TextLine
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return int
     */
    public function getFormat(): int
    {
        return $this->format;
    }

    /**
     * @param int $format
     * @return TextLine
     */
    public function setFormat(int $format): TextLine
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return void
     */
    public function resetColorFormat(): void
    {
        $this->color = UIEnum::COLOR_NO_SET;
        $this->format = UIEnum::FORMAT_NO_SET;
    }


    // === functions ===
    private function toString(): string
    {
        return sprintf(
            "%s%s%s%s",
            str_repeat(" ", $this->indentLevel * IndentLevelEnum::AMOUNT_SPACES),
            $this->icon ? $this->icon . ' ' : '',
            $this->tag ? sprintf("[%s] ", $this->tag) : '',
            $this->text
        );
    }

    public function print(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = count($values) ? vsprintf($format, $values) : $format;
        // print
        $finalText = sprintf("%s\n", $this->toString());
        //     case 1: set both color and format
        if ($this->color !== UIEnum::COLOR_NO_SET && $this->format !== UIEnum::FORMAT_NO_SET) {
            echo self::colorFormat($finalText, $this->color, $this->format);
            //
            // case 2: set color only
        } else if ($this->color !== UIEnum::COLOR_NO_SET) {
            echo self::color($finalText, $this->color);
            //
            // case 3: no set both color and format
        } else {
            echo $finalText;
        }
        //
        return $this;
    }

    public function printTitle(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = count($values) ? vsprintf($format, $values) : $format;
        // print
        echo self::colorFormat(sprintf("=== %s ===\n", $this->toString()),
            UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD);
        //
        return $this;
    }

    public function printSubTitle(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = count($values) ? vsprintf($format, $values) : $format;
        // print
        echo self::color(sprintf("-- %s --\n", $this->toString()), UIEnum::COLOR_BLUE);
        //
        return $this;
    }

    public function printSeparatorLine(): TextLine
    {
        $this->print($this->indentLevel === IndentLevelEnum::MAIN_LINE
            ? str_repeat("=", 3) : str_repeat("-", 3));
        //
        return $this;
    }

    /**
     * @param bool $condition
     * @param string $messageSuccess
     * @param string $messageError
     * @return void
     */
    public function printCondition(bool $condition, string $messageSuccess, string $messageError): TextLine
    {
        $condition ? $this->setTag(TagEnum::SUCCESS)->print($messageSuccess)
            : $this->setTag(TagEnum::ERROR)->print($messageError);
        //
        return $this;
    }


}

// [REMOVED] namespace App\Classes;

class GitHubRepositoryInfo
{
    /** @var string */
    private $repositoryName;

    /** @var string */
    private $username;

    /** @var bool */
    private $isGitHubAction;

    /** @var string */
    private $currentBranch;

    /** @var string */
    private $currentWorkspaceDir;

    /**
     * @param string $repositoryName
     * @param string $username
     * @param bool $isGitHubAction
     */
    public function __construct(string $repositoryName, string $username, bool $isGitHubAction = false)
    {
        $this->repositoryName = $repositoryName;
        $this->username = $username;
        $this->isGitHubAction = $isGitHubAction;
    }

    /**
     * @return string
     */
    public function getRepositoryName(): string
    {
        return $this->repositoryName;
    }

    /**
     * @param string $repositoryName
     * @return GitHubRepositoryInfo
     */
    public function setRepositoryName(string $repositoryName): GitHubRepositoryInfo
    {
        $this->repositoryName = $repositoryName;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return GitHubRepositoryInfo
     */
    public function setUsername(string $username): GitHubRepositoryInfo
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGitHubAction(): bool
    {
        return $this->isGitHubAction;
    }

    /**
     * @param bool $isGitHubAction
     * @return GitHubRepositoryInfo
     */
    public function setIsGitHubAction(bool $isGitHubAction): GitHubRepositoryInfo
    {
        $this->isGitHubAction = $isGitHubAction;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentBranch(): string
    {
        return $this->currentBranch;
    }

    /**
     * @param string $currentBranch
     * @return GitHubRepositoryInfo
     */
    public function setCurrentBranch(string $currentBranch): GitHubRepositoryInfo
    {
        $this->currentBranch = $currentBranch;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentWorkspaceDir(): string
    {
        return $this->currentWorkspaceDir;
    }

    /**
     * @param string $currentWorkspaceDir
     * @return GitHubRepositoryInfo
     */
    public function setCurrentWorkspaceDir(string $currentWorkspaceDir): GitHubRepositoryInfo
    {
        $this->currentWorkspaceDir = $currentWorkspaceDir;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentRepositoryDir(): string
    {
        return sprintf('%s/%s', $this->currentWorkspaceDir, $this->repositoryName);
    }

}

// [REMOVED] namespace App\Classes;

// [REMOVED] use DateInterval;

class Duration
{
    /** @var DateInterval */
    private $dateInterval;

    /** @var int */
    public $totalMinutes;

    /** @var int */
    public $totalSeconds;

    /**
     * @param DateInterval $dateInterval
     */
    public function __construct(DateInterval $dateInterval)
    {
        $this->dateInterval = $dateInterval;
        //
        $this->totalMinutes = (int)($this->dateInterval->days * 24 * 60 + $this->dateInterval->h * 60 + $this->dateInterval->i);
        $this->totalSeconds = (int)($this->dateInterval->days * 24 * 60 * 60 + $this->dateInterval->h * 60 * 60
            + $this->dateInterval->i * 60 + $this->dateInterval->s);
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return sprintf("%d minute%s %d second%s",
            $this->totalMinutes, $this->totalMinutes > 1 ? "s" : "",
            $this->dateInterval->s, $this->dateInterval->s > 1 ? "s" : ""
        );
    }
}

// [REMOVED] namespace App\Enum;

// [REMOVED] use App\OpsApp;

class CommandEnum
{
    // === this app commands ===
    const HELP = 'help';
    const RELEASE = 'release';
    const VERSION = 'version';
    const SYNC = 'sync';

    // === AWS related DATA commands
    const LOAD_ENV_OPS = 'load-env-ops';
    const GET_SECRET_ENV = 'get-secret-env';
    const ELB_UPDATE_VERSION = 'elb-update-version';

    // === Git/GitHub ===
    const BRANCH = 'branch';
    const REPOSITORY = 'repository';
    const HEAD_COMMIT_ID = 'head-commit-id';
    const HANDLE_CACHES_AND_GIT = 'handle-caches-and-git';
    const FORCE_CHECKOUT = 'force-checkout';
    //        GitHub Actions
    const BUILD_ALL_PROJECTS = 'build-all-projects';

    // === Docker ===
    const DOCKER_KEEP_IMAGE_BY = 'docker-keep-image-by';
    const DOCKER_FILE_ADD_ENVS = 'dockerfile-add-envs';

    // === utils ===
    const HOME_DIR = 'home-dir';
    const SCRIPT_DIR = 'script-dir';
    const WORKING_DIR = 'working-dir';
    const REPLACE_TEXT_IN_FILE = 'replace-text-in-file';
    const SLACK = 'slack';
    const TMP = 'tmp';
    const POST_WORK = 'post-work';
    const CLEAR_OPS_DIR = 'clear-ops-dir';

    // === ops private commands ===
    const GET_S3_WHITE_LIST_IPS_DEVELOPMENT = 'get-s3-white-list-ips-develop';
    const UPDATE_GITHUB_TOKEN_ALL_PROJECT = 'update-github-token-all-project';

    // === validation ===
    const VALIDATE = 'validate';

    // === UI/Text ===
    const TITLE = 'title';
    const SUB_TITLE = 'sub-title';

    /**
     * @return array
     * key => value | key is command, value is description
     */
    public static function SUPPORT_COMMANDS():array
    {
        return [
            // group title
            "OPS APP" => [],
            self::HELP => ['show list support command and usage'],
            self::RELEASE => [
                sprintf("combine all PHP files into '.release/OpsApp.php' and install a alias '%s'",OpsApp::APP_MAIN_COMMAND),
                "default version increasing is 'patch'",
                "feature should be 'minor'",
            ],
            self::VERSION => ["show app version"],
            self::SYNC => [sprintf("sync new release code to caches dir and create an alias '%s'", OpsApp::APP_MAIN_COMMAND)],
            // group title
            "AWS Related" => [],
            self::LOAD_ENV_OPS => [
                '[AWS Secret Manager] [CREDENTIAL REQUIRED] load env ops, usage in Shell:',
                sprintf('            eval "$(%s load-env-ops)"    ', OpsApp::APP_MAIN_COMMAND)
            ],
            self::GET_SECRET_ENV => ["[AWS Secret Manager] [CREDENTIAL REQUIRED] get .env | params:  secretName, customENVName"],
            self::ELB_UPDATE_VERSION => ["[AWS Elastic Beanstalk] create a new version and update an environment"],
            // group title
            "GIT / GITHUB" => [],
            self::BRANCH => ['get git branch / GitHub branch'],
            self::REPOSITORY => ['get GitHub repository name'],
            self::HEAD_COMMIT_ID => ['get head commit id of branch'],
            self::HANDLE_CACHES_AND_GIT => ['handle GitHub repository in caches directory'],
            self::FORCE_CHECKOUT => [
                'force checkout a GitHub repository with specific branch',
                '.e.g to test source code in the server'
            ],
            //        GitHub Actions
            self::BUILD_ALL_PROJECTS => ['[GitHub Actions] build all projects to keep the GitHub runner token connecting'], // ES-2381
            // group title
            "DOCKER" => [],
            self::DOCKER_KEEP_IMAGE_BY => ['Keep image by repository and tag, use for keep latest image. Required:  imageRepository imageTag'],
            self::DOCKER_FILE_ADD_ENVS => ['add ENVs into Dockerfile below FROM line. Required: DockerfilePath, secretName'],
            // group title
            "UTILS" => [],
            self::HOME_DIR => ['return home directory of machine / server'],
            self::SCRIPT_DIR => ['return directory of script'],
            self::WORKING_DIR => ['get root project directory / current working directory'],
            self::REPLACE_TEXT_IN_FILE => [sprintf('php %s replace-text-in-file "search text" "replace text" "file path"', OpsApp::APP_MAIN_COMMAND)],
            self::SLACK => ["notify a message to Slack"],
            self::TMP => [
                'handle temporary directory (tmp)',
                "use 'tmp add' to add new tmp dir",
                "use 'tmp remove' to remove tmp dir"
            ],
            self::POST_WORK => ["do post works. Optional: add param 'skip-check-dir' to skip check dir"],
            self::CLEAR_OPS_DIR => ["clear _ops directory, usually use in Docker image"],
            // group title
            "PRIVATE" => [],
            self::GET_S3_WHITE_LIST_IPS_DEVELOPMENT => ['[PRIVATE] get S3 whitelist IPs to add to AWS Policy'],
            self::UPDATE_GITHUB_TOKEN_ALL_PROJECT => ['[PRIVATE] update token all projects in workspace'],
            // group title
            "VALIDATION" => [],
            self::VALIDATE => [
                "required: 'set -e' in bash file",
                sprintf('  should combine with exit 1, eg:   php %s validate TYPE | exit 1', OpsApp::APP_MAIN_COMMAND),
                '  support TYPEs:',
                '    branch  : to only allow develop, staging, master',
                '    docker  : docker should is running',
                '    device  : should pass env var: DEVICE in your first command',
                '    file-contains-text  : check if file should contain a text or some texts',
            ],
            // group title
            "UI/Text" => [],
            self::TITLE => ["print a title in terminal/console"],
            self::SUB_TITLE => ["print a sub title in terminal/console"],
        ];
    }
}

// [REMOVED] namespace App\Enum;

// [REMOVED] use App\Classes\GitHubRepositoryInfo;

class GitHubEnum
{
    // === GitHub commands ===
    public const INIT_REPOSITORY_COMMAND = 'git init';
    public const RESET_BRANCH_COMMAND = 'git reset --hard HEAD'; // rollback all changing
    public const GET_BRANCH_COMMAND = "git symbolic-ref HEAD | sed 's/refs\/heads\///g'";
    public const PULL_COMMAND = 'git pull'; // get the newest code
    public const ADD_ALL_FILES_COMMAND = 'git add -A';
    public const PUSH_COMMAND = 'git push';
    public const GET_REMOTE_ORIGIN_URL_COMMAND = 'git config --get remote.origin.url';
    public const GET_REPOSITORY_DIR_COMMAND = 'git rev-parse --show-toplevel';
    public const GET_HEAD_COMMIT_ID_COMMAND = 'git rev-parse --short HEAD';

    // === Git branches ===
    public const MAIN = 'main';
    public const MASTER = 'master';
    public const STAGING = 'staging';
    public const DEVELOP = 'develop';

    /**
     * @return array
     */
    public static function GET_REPOSITORIES_INFO(): array
    {
        return [
            new GitHubRepositoryInfo('engage-api', 'infohkengage', true),
            new GitHubRepositoryInfo('engage-spa', 'infohkengage', true),
            new GitHubRepositoryInfo('engage-booking-api', 'infohkengage', true),
            new GitHubRepositoryInfo('engage-booking-spa', 'infohkengage', true),
            new GitHubRepositoryInfo('invoice-service', 'infohkengage', true),
            new GitHubRepositoryInfo('payment-service', 'infohkengage', true),
            new GitHubRepositoryInfo('integration-api', 'infohkengage', true),
            new GitHubRepositoryInfo('email-service', 'infohkengage', true),
            //
            new GitHubRepositoryInfo('engage-api-deploy', 'infohkengage'),
            //
            new GitHubRepositoryInfo('engage-database-utils', 'congnqnexlesoft'),
            new GitHubRepositoryInfo('ops-app', 'congnqnexlesoft'),
            new GitHubRepositoryInfo('docker-base-images', 'congnqnexlesoft'),
            new GitHubRepositoryInfo('engage-selenium-test-1', 'congnqnexlesoft'),
        ];
    }
}

// [REMOVED] namespace App\Enum;

class IndentLevelEnum
{
    const AMOUNT_SPACES = 2; // per indent

    const MAIN_LINE = 0; // no indent
    const ITEM_LINE = 1; // indent with 4 spaces
    const SUB_ITEM_LINE = 2; // indent with 8 spaces
    const LEVEL_3 = 3; // indent with 12 spaces
    const LEVEL_4 = 4; // indent with 16 spaces
    const LEVEL_5 = 5; // indent with 16 spaces
}

// [REMOVED] namespace App\Enum;

class IconEnum
{
    const X = 'X';
    const CHECK = '✔';
    const HYPHEN = '-';
    const PLUS = '+';
    const CHEVRON_RIGHT = '>';
    const DOT = '∘';

}

// [REMOVED] namespace App\Enum;

class TagEnum
{
    const NONE ='';
    const VALIDATION = 'VALIDATION';
    const INFO ='INFO';
    const SUCCESS = 'SUCCESS';
    const ERROR = 'ERROR';
    const PARAMS = 'PARAMS';
    const ENV = 'ENV';
    const FORMAT = 'FORMAT';
    const WORK = 'WORK';
    const GIT = 'GIT/GITHUB';
    const DOCKER = 'DOCKER';
    const SLACK = 'SLACK';
}

// [REMOVED] namespace App\Enum;

/**
 * reference https://en.wikipedia.org/wiki/ANSI_escape | SGR (Select Graphic Rendition) parameters | Colors
 */
class UIEnum
{
    // === colors ===
    const COLOR_NO_SET = 99999;
    const COLOR_RED = 31;
    const COLOR_GREEN = 32;
    const COLOR_BLUE = 34;

    // === text format ===
    const FORMAT_NO_SET = 99999;
    const FORMAT_NONE = 0;
    const FORMAT_BOLD = 1;
    const FORMAT_ITALIC = 3;
    const FORMAT_UNDERLINE = 4;
}

// [REMOVED] namespace App\Enum;

class DockerEnum
{
    const FROM = 'FROM';
    const ENV = 'ENV';
}

// [REMOVED] namespace App\Enum;

class ValidationTypeEnum
{
    const BRANCH = 'branch';
    const DOCKER = 'docker';
    const DEVICE = 'device';
    const FILE_CONTAINS_TEXT = 'file-contains-text';

    // ===
    const SUPPORT_LIST = [self::BRANCH, self::DOCKER, self::DEVICE, self::FILE_CONTAINS_TEXT];
}

// [REMOVED] namespace App\Enum;

class PostWorkEnum
{
    const SKIP_CHECK_DIR = 'skip-check-dir';
}

// [REMOVED] namespace App\Helpers;

// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Classes\Process;
// [REMOVED] use App\Traits\ConsoleUITrait;

/**
 * this is a DIRectory helper / folder helper
 */
class DirHelper
{
    use ConsoleUITrait;

    /**
     * get home directory / get root directory of user
     *
     * @param string|null $withSubDirOrFile
     * @return string
     */
    public static function getHomeDir(string $withSubDirOrFile = null): string
    {
        return $withSubDirOrFile
            ? sprintf("%s/%s", $_SERVER['HOME'], $withSubDirOrFile)
            : $_SERVER['HOME'];
    }

    /**
     * @param string|null $withSubDirOrFile
     * @return string
     */
    public static function getWorkingDir(string $withSubDirOrFile = null): string
    {
        return $withSubDirOrFile
            ? sprintf("%s/%s", $_SERVER['PWD'], $withSubDirOrFile)
            : $_SERVER['PWD'];
    }

    /**
     * @return string
     */
    public static function getProjectDirName(): string
    {
        return basename(self::getWorkingDir());
    }

    /**
     * get current working directory of script
     * @return string
     */
    public static function getScriptDir(): string
    {
        $scriptDir = substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - strlen(basename($_SERVER['SCRIPT_FILENAME'])) - 1);
        return self::getWorkingDir($scriptDir);
    }

    // backup code
//    public static function getRepositoryDir()
//    {
//        return exec('git rev-parse --show-toplevel');
//    }

    /**
     * handle tmp directory
     * - tmp add : create a tmp directory
     * - tmp remove : remove the tmp directory
     *
     * @param array $argv
     * @return void
     */
    public static function tmp(array $argv): void
    {
        switch ($argv[2] ?? null) {
            case 'add':
                if (is_dir(self::getWorkingDir('tmp'))) {
                    $commands[] = sprintf("rm -rf '%s'", self::getWorkingDir('tmp'));
                }
                $commands[] = sprintf("mkdir -p '%s'", self::getWorkingDir('tmp'));
                (new Process("Add tmp dir", self::getWorkingDir(), $commands))
                    ->execMultiInWorkDir()->printOutput();
                // validate result
                self::LineNew()->printCondition(is_dir(self::getWorkingDir('tmp')),
                    'create a tmp dir successfully', 'create a tmp dir failure');
                break;
            case 'remove':
                if (is_dir(self::getWorkingDir('tmp'))) {
                    $commands[] = sprintf("rm -rf '%s'", self::getWorkingDir('tmp'));
                    (new Process("Remove tmp dir", self::getWorkingDir(), $commands))
                        ->execMultiInWorkDir()->printOutput();
                    // validate result
                    $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'tmp'", self::getWorkingDir()));
                    self::LineNew()->printCondition(!$checkTmpDir,
                        'remove a tmp dir successfully', 'remove a tmp dir failure');
                } else {
                    self::LineNew()->print("tmp directory doesn't exist, do nothing");
                }
                break;
            default:
                self::LineTag(TagEnum::ERROR)->print("missing action, action should be 'add' or 'remove'");
                break;
        }
    }

    /**
     * .e.g usage   DIR::getClassPath(TextLine::class)
     * class name should follow PSR-4
     * @param string $ClassDotClass
     * @return void
     */
    public static function getClassPathAndFileName(string $ClassDotClass): string
    {
        return lcfirst(sprintf("%s.php", str_replace("\\", "/", $ClassDotClass)));
    }


}

// [REMOVED] namespace App\Helpers;

// [REMOVED] use App\Classes\GitHubRepositoryInfo;
// [REMOVED] use App\Classes\Release;
// [REMOVED] use App\Enum\GitHubEnum;
// [REMOVED] use App\Enum\IconEnum;
// [REMOVED] use App\Enum\IndentLevelEnum;
// [REMOVED] use App\Enum\PostWorkEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Enum\UIEnum;
// [REMOVED] use App\Enum\ValidationTypeEnum;
// [REMOVED] use App\Classes\Process;
// [REMOVED] use App\OpsApp;
// [REMOVED] use App\Traits\ConsoleUITrait;

/**
 * This is Ops helper
 */
class OPSHelper
{
    use ConsoleUITrait;

    const COMPOSER_CONFIG_GITHUB_AUTH_FILE = 'auth.json';

    public static function getS3WhiteListIpsDevelopment(): string
    {

        $NEXLE_IPS = [
            '115.73.208.177', // Nexle VPN
            '115.73.208.182', // Nexle HCM office - IP 1
            '115.73.208.183', // Nexle HCM office - IP 2
            '14.161.25.117', // Nexle HCM office - IP 3
            '113.160.235.76', // Nexle DN office NEW (2 2024)
        ];
        $GITHUB_RUNNER_SERVER_IP = '18.167.126.148';
        $EC2DevelopIp = exec("echo $(curl https://develop-api.engageplus.io/api/booking/IP-QYIa20HxwQ)");
        $EC2StagingIp = exec("echo $(curl https://staging-api.engageplus.io/api/booking/IP-QYIa20HxwQ)");
        //
        $S3_WHITELIST_IP_DEVELOPMENT = array_merge($NEXLE_IPS, [
            $GITHUB_RUNNER_SERVER_IP,
            $EC2DevelopIp,
            $EC2StagingIp
        ]);
        //
        return sprintf("\n\n%s\n\n", json_encode($S3_WHITELIST_IP_DEVELOPMENT));
    }

    public static function updateGitHubTokenAllProjects()
    {
        $GITHUB_PERSONAL_ACCESS_TOKEN_NEW = readline("Please input new GITHUB_PERSONAL_ACCESS_TOKEN? ");
        if (!$GITHUB_PERSONAL_ACCESS_TOKEN_NEW) {
            self::LineTag(TagEnum::ERROR)->print("GitHub Personal Token should be string");
            exit(); // END
        }
//
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        self::LineNew()->print("WORKSPACE DIR = $workspaceDir");
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            self::LineIcon(IconEnum::PLUS)->print("Project '%s > %s': %s",
                $repoInfo->getUsername(), $repoInfo->getRepositoryName(),
                is_dir(sprintf("%s/%s", $workspaceDir, $repoInfo->getRepositoryName())) ? "✔" : "X"
            );
        }
// update token
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            $projectDir = sprintf("%s/%s", $workspaceDir, $repoInfo->getRepositoryName());
            if (is_dir($projectDir)) {
                $output = null;
                $resultCode = null;
                exec(join(';', [
                    sprintf("cd \"%s\"", $projectDir), # jump into this directory
                    sprintf("git remote set-url origin https://%s@github.com/%s/%s.git", $GITHUB_PERSONAL_ACCESS_TOKEN_NEW, $repoInfo->getUsername(), $repoInfo->getRepositoryName()),
                ]), $output, $resultCode);
                // print output
                foreach ($output as $line) {
                    self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::PLUS)->print($line);
                }
            }
        }
    }

    /**
     * - sync new release code to caches dir in machine '~/.caches_engageplus/ops-app'
     * - create an alias 'ops-app' link to the release file at '~/.caches_engageplus/ops-app/.release/OpsApp.php'
     */
    public static function sync()
    {
        self::LineNew()->printTitle(__FUNCTION__);
        // load env into PHP
        self::parseEnoughDataForSync(AWSHelper::loadOpsEnvAndHandleMore());
        // load caches of this source code
        GitHubHelper::handleCachesAndGit([
            'script path',
            'command-name', // param 1
            'ops-app', // param 2, in this case is repository
            'main', // param 3, in this case is branch
        ]);
        // create an alias 'ops-app'
        self::createAlias();
        //
        self::LineNew()->printSeparatorLine()
            ->setTag(TagEnum::SUCCESS)->print("sync done");
        self::LineNew()->printSeparatorLine();
        // show open new session to show right version
        (new Process("CHECK A NEW VERSION", DirHelper::getWorkingDir(), [
            'ops-app version'
        ]))->execMultiInWorkDir(true)->printOutput();
        //
        self::LineNew()->printSeparatorLine();
    }

    /**
     * create alias of release app (this app) in shell configuration files
     *
     * @return void
     */
    private static function createAlias()
    {
        $EngagePlusCachesRepositoryOpsAppReleasePath = sprintf("%s/ops-app/%s", getenv('ENGAGEPLUS_CACHES_DIR'), Release::RELEASE_PATH);
        $alias = sprintf("alias %s=\"php %s\"", OpsApp::APP_MAIN_COMMAND, $EngagePlusCachesRepositoryOpsAppReleasePath);
        $shellConfigurationFiles = [
            DirHelper::getHomeDir('.zshrc'), // Mac
            DirHelper::getHomeDir('.bashrc'), // Ubuntu
        ];
        foreach ($shellConfigurationFiles as $shellConfigurationFile) {
            if (is_file($shellConfigurationFile)) {
                self::lineNew()->printSubTitle("create alias '%s' at '%s'", OpsApp::APP_MAIN_COMMAND, $shellConfigurationFile);
                // already setup
                if (StrHelper::contains(file_get_contents($shellConfigurationFile), $alias)) {
                    self::lineNew()->setIcon(IconEnum::DOT)->print("already setup alias '%s'", OpsApp::APP_MAIN_COMMAND);
                } else {
                    // setup alias
                    //    remove old alias (wrong path, old date alias)
                    $oldAliases = StrHelper::findLinesContainsTextInFile($shellConfigurationFile, OpsApp::APP_MAIN_COMMAND);
                    foreach ($oldAliases as $oldAlias) {
                        StrHelper::replaceTextInFile([
                            'script path', 'command-name', // param 0,1
                            $oldAlias, '', $shellConfigurationFile
                        ]);
                    }
                    //    add new alias
                    if (file_put_contents($shellConfigurationFile, $alias . PHP_EOL, FILE_APPEND)) {
                        self::lineNew()->setIcon(IconEnum::CHECK)->print("adding alias done");
                    } else {
                        self::lineNew()->setIcon(IconEnum::X)->print("adding alias failure");
                    }
                }
                // validate alias
                self::validate([
                    'script path', 'command-name', // param 0,1
                    ValidationTypeEnum::FILE_CONTAINS_TEXT, "$shellConfigurationFile", $alias
                ]);
            }
        }
    }

    /**
     * need to get
     * - ENGAGEPLUS_CACHES_FOLDER
     * - ENGAGEPLUS_CACHES_DIR="$(php ~/ops-app home-dir)/${ENGAGEPLUS_CACHES_FOLDER}"
     * - GITHUB_PERSONAL_ACCESS_TOKEN
     * and put to PHP env
     * @return void
     */
    public static function parseEnoughDataForSync(string $opsEnvAllData)
    {
        $tempArr = explode(PHP_EOL, $opsEnvAllData);
        foreach ($tempArr as $line) {
            if (strpos($line, "export ENGAGEPLUS_CACHES_FOLDER") !== false) {
                $key = explode('=', str_replace('export ', '', $line), 2)[0];
                $value = explode('=', str_replace('export ', '', $line), 2)[1];
                $value = trim($value, '"');
                putenv("$key=$value");
            }
            if (strpos($line, "export GITHUB_PERSONAL_ACCESS_TOKEN") !== false) {
                putenv(trim(str_replace('export ', '', $line), '"'));
            }
        }
        //
        putenv(sprintf("ENGAGEPLUS_CACHES_DIR=%s/%s", DirHelper::getHomeDir(), getenv('ENGAGEPLUS_CACHES_FOLDER')));
    }

    /**
     * do some post works:
     * - cleanup
     * @param array $argv
     * @return void
     */
    public static function postWork(array $argv): void
    {
        // === param ===
        $isSkipCheckDir = ($argv[2] ?? null) === PostWorkEnum::SKIP_CHECK_DIR;
        //
        self::LineNew()->printTitle("Post works");
        if ($isSkipCheckDir) {
            self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)
                ->print("skip check execution directory");
        }
        $isDoNothing = true;
        // === cleanup ===
        //    clear .env, .conf-ryt
        if (getenv('ENGAGEPLUS_CACHES_FOLDER')
            && StrHelper::contains(DirHelper::getWorkingDir(), getenv('ENGAGEPLUS_CACHES_FOLDER'))) {
            //        .env
            if (is_file(DirHelper::getWorkingDir('.env'))) {
                (new Process("Remove .env", DirHelper::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DirHelper::getWorkingDir('.env'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '.env'", DirHelper::getWorkingDir()));
                self::LineNew()->printCondition(!$checkTmpDir,
                    "remove '.env' file successfully", "remove '.env' file failed");
                //
                $isDoNothing = false;
            }
            //        .conf-ryt
            if (is_file(DirHelper::getWorkingDir('.conf-ryt'))) {
                (new Process("Remove .conf-ryt", DirHelper::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DirHelper::getWorkingDir('.conf-ryt'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '.conf-ryt'", DirHelper::getWorkingDir()));
                self::LineNew()->printCondition(!$checkTmpDir,
                    "remove a '.conf-ryt' file successfully", "remove a '.conf-ryt' file failed");
                //
                $isDoNothing = false;
            }
            //        [payment-service] payment-credentials.json
            if (is_file(DirHelper::getWorkingDir('payment-credentials.json'))) {
                (new Process("Remove 'payment-credentials.json'", DirHelper::getWorkingDir(), [
                    sprintf("rm -rf '%s'", DirHelper::getWorkingDir('payment-credentials.json'))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'payment-credentials.json'", DirHelper::getWorkingDir()));
                self::LineNew()->printCondition(!$checkTmpDir,
                    "remove a 'payment-credentials.json' file successfully", "remove a 'payment-credentials.json' file failed");
                //
                $isDoNothing = false;
            }
        }
        //    tmp dir (PHP project)
        if (is_dir(DirHelper::getWorkingDir('tmp'))) {
            (new Process("Remove tmp dir", DirHelper::getWorkingDir(), [
                sprintf("rm -rf '%s'", DirHelper::getWorkingDir('tmp'))
            ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'tmp'", DirHelper::getWorkingDir()));
            self::LineNew()->printCondition(!$checkTmpDir,
                'remove a tmp dir successfully', 'remove a tmp dir failure');
            //
            $isDoNothing = false;
        }
        //    dist dir (Angular project)
        if (is_dir(DirHelper::getWorkingDir('dist'))) {
            (new Process("Remove dist dir", DirHelper::getWorkingDir(), [
                sprintf("rm -rf '%s'", DirHelper::getWorkingDir('dist'))
            ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
            // validate result
            $checkTmpDir = exec(sprintf("cd '%s' && ls | grep 'dist'", DirHelper::getWorkingDir()));
            self::LineNew()->printCondition(!$checkTmpDir,
                'remove a dist dir successfully', 'remove a dist dir failure');
            //
            $isDoNothing = false;
        }
        //    composer config file: auth.json
        if (is_file(DirHelper::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE))) {
            $authJsonContent = file_get_contents(DirHelper::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE));
            if (StrHelper::contains($authJsonContent, "github-oauth") && StrHelper::contains($authJsonContent, "github.com")) {
                (new Process("Remove composer config file", DirHelper::getWorkingDir(), [
                    sprintf("rm -f '%s'", DirHelper::getWorkingDir(self::COMPOSER_CONFIG_GITHUB_AUTH_FILE))
                ]))->execMultiInWorkDir($isSkipCheckDir)->printOutput();
                // validate result
                $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '%s'", DirHelper::getWorkingDir(), self::COMPOSER_CONFIG_GITHUB_AUTH_FILE));
                self::LineNew()->printCondition(
                    !$checkTmpDir,
                    sprintf("remove file '%s' successfully", self::COMPOSER_CONFIG_GITHUB_AUTH_FILE),
                    sprintf("remove file '%s' failed", self::COMPOSER_CONFIG_GITHUB_AUTH_FILE)
                );
                //
                $isDoNothing = false;
            }
        }
        //    dangling Docker images / <none> Docker images
        if (DockerHelper::isDockerInstalled()) {
            if (DockerHelper::isDanglingImages()) {
                DockerHelper::removeDanglingImages();
                //
                $isDoNothing = false;
            }
        }

        // === end cleanup ===
        //
        if ($isDoNothing) {
            self::LineNew()->print("do nothing");
        }
        self::LineNew()->printSeparatorLine();
    }

    public static function clearOpsDir(): void
    {
        self::LineNew()->printTitle("Clear _ops directory");
        (new Process("Clear _ops directory", DirHelper::getWorkingDir(), [
            sprintf("rm -rf '%s'", DirHelper::getWorkingDir('_ops'))
        ]))->execMultiInWorkDir(true)->printOutput();
        // validate result
        $checkTmpDir = exec(sprintf("cd '%s' && ls | grep '_ops'", DirHelper::getWorkingDir()));
        self::LineNew()->printCondition(!$checkTmpDir, "clear _ops dir successfully", "clear _ops dir failed");
    }

    /**
     * also notify an error message,
     * eg: ['VAR1', 'VAR2']
     * @param array $envVars
     * @return bool
     */
    public static function validateEnvVars(array $envVars): bool
    {
        $envVarsMissing = [];
        foreach ($envVars as $envVar) {
            if (!getenv($envVar)) $envVarsMissing[] = $envVar;
        }
        if (count($envVarsMissing) > 0) {
            self::LineTagMultiple([TagEnum::ERROR, TagEnum::ENV])->print("missing %s", join(" or ", $envVarsMissing));
            return false; // END | case error
        }
        return true; // END | case OK
    }

    public static function validate(array $argv)
    {
        switch ($argv[2] ?? null) {
            case ValidationTypeEnum::BRANCH:
                self::validateBranch();
                break;
            case ValidationTypeEnum::DOCKER:
                self::validateDocker();
                break;
            case ValidationTypeEnum::DEVICE:
                self::validateDevice();
                break;
            case ValidationTypeEnum::FILE_CONTAINS_TEXT:
                self::validateFileContainsText($argv);
                break;
            default:
                self::LineTag(TagEnum::ERROR)->print("invalid action, current support:  %s", join(", ", ValidationTypeEnum::SUPPORT_LIST))
                    ->print("should be like eg:   '%s' validate branch", OpsApp::APP_MAIN_COMMAND);
                break;
        }
    }

    /**
     * allow branches: develop, staging, master
     * should combine with exit 1 in shell:
     *     php ~/ops-app validate branch || exit 1
     * @return void
     */
    private static function validateBranch()
    {
        if (in_array(getenv('BRANCH'), ['develop', 'staging', 'master'])) {
            self::LineTag(TagEnum::SUCCESS)->print("validation branch got OK result: %s", getenv('BRANCH'));
        } else {
            self::LineTag(TagEnum::ERROR)->print("Invalid branch to build | current branch is '%s'", getenv('BRANCH'));
            exit(1); // END app
        }
    }

    /**
     * Docker should is running
     * should combine with exit 1 in shell:
     *      php ~/ops-app validate docker || exit 1
     */
    private static function validateDocker()
    {
        $dockerServer = exec("docker version | grep 'Server:'");
        if (trim($dockerServer)) {
            self::LineTag(TagEnum::SUCCESS)->print("Docker is running: $dockerServer");
        } else {
            self::LineTag(TagEnum::ERROR)->print("Docker isn't running. Please start Docker app.");
            exit(1); // END app
        }
    }

    /**
     * should have env var: BRANCH
     *     php ~/ops-app validate device || exit 1
     * @return void
     */
    private static function validateDevice()
    {
        if (getenv('DEVICE')) {
            self::LineTag(TagEnum::SUCCESS)->print("validation device got OK result: %s", getenv('DEVICE'));
        } else {
            self::LineTag(TagEnum::ERROR)->print("Invalid device | should pass in your command");
            exit(1); // END app
        }
    }

    private static function validateFileContainsText(array $argv)
    {
        // validate
        $filePath = $argv[3] ?? null;
        $searchTextArr = [];
        for ($i = 4; $i < 20; $i++) {
            if (count($argv) > $i)
                if ($argv[$i]) {
                    $searchTextArr[] = $argv[$i];
                }
        }
        if (!$filePath || !count($searchTextArr)) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])->print("missing filePath or searchText (can path multiple searchText1 searchText2)");
            exit(1);
        }
        if (!is_file($filePath)) {
            self::LineTag(TagEnum::ERROR)->print("'%s' does not exist", $filePath);
            exit(1);
        }
        // handle
        $fileContent = file_get_contents($filePath);
        $validationResult = [];
        foreach ($searchTextArr as $searchText) {
            $validationResult[] = [
                'searchText' => $searchText,
                'isContains' => StrHelper::contains($fileContent, $searchText)
            ];
        }
        $amountValidationPass = count(array_filter($validationResult, function ($item) {
            return $item['isContains'];
        }));
        if ($amountValidationPass === count($searchTextArr)) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::SUCCESS])->print("file '%s' contains text(s): '%s'", $filePath, join("', '", $searchTextArr));
        } else {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->print("file '%s' does not contains (some) text(s):", $filePath);
            foreach ($validationResult as $result) {
                self::LineIndent(IndentLevelEnum::ITEM_LINE)
                    ->setIcon($result['isContains'] ? IconEnum::CHECK : IconEnum::X)
                    ->setColor($result['isContains'] ? UIEnum::COLOR_GREEN : UIEnum::COLOR_RED)
                    ->print($result['searchText']);
            }
            exit(1);
        }
    }
}

// [REMOVED] namespace App\Helpers;

// [REMOVED] use App\Classes\Duration;
// [REMOVED] use App\Classes\GitHubRepositoryInfo;
// [REMOVED] use App\Classes\Process;
// [REMOVED] use App\Enum\CommandEnum;
// [REMOVED] use App\Enum\GitHubEnum;
// [REMOVED] use App\Enum\IconEnum;
// [REMOVED] use App\Enum\IndentLevelEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Services\SlackService;
// [REMOVED] use App\Traits\ConsoleUITrait;
// [REMOVED] use DateTime;

/**
 * This is a GitHub helper
 */
class GitHubHelper
{
    use ConsoleUITrait;

    /**
     * @param string $name
     * @return false|GitHubRepositoryInfo|null
     */
    public static function getRepositoryInfoByName(string $name)
    {
        $repoArr = array_filter(GitHubEnum::GET_REPOSITORIES_INFO(), function ($repository) use ($name) {
            /** @var GitHubRepositoryInfo $repository */
            return $repository->getRepositoryName() === $name;
        });
        return reset($repoArr);
    }

    /**
     * get current GitHub info, will return
     * [REMOTE_ORIGIN_URL, GITHUB_PERSONAL_TOKEN, USERNAME, REPOSITORY_NAME]
     *
     * @param string|null $remoteOriginUrl
     * @return array
     */
    public static function parseGitHub(string $remoteOriginUrl = null): array
    {
        $remoteOriginUrl = $remoteOriginUrl ?? self::getRemoteOriginUrl_Current();
        return [
            $remoteOriginUrl,
            strpos($remoteOriginUrl, "@") !== false
                ? str_replace('https://', '', explode('@', $remoteOriginUrl)[0])
                : null,
            basename(str_replace(basename($remoteOriginUrl), '', $remoteOriginUrl)),
            basename(str_replace('.git', '', $remoteOriginUrl))
        ];
    }

    public static function getRemoteOriginUrl_Current(): ?string
    {
        return exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND);
    }

    /**
     * @param string $repositoryName
     * @param string|null $GitHubPersonalAccessToken
     * @return string
     */
    public static function getRemoteOriginUrl_Custom(string $repositoryName, string $GitHubPersonalAccessToken = null): string
    {
        return $GitHubPersonalAccessToken
            ? sprintf("https://%s@github.com/%s/%s.git", $GitHubPersonalAccessToken, self::getRepositoryInfoByName($repositoryName)->getUsername(), $repositoryName)
            : sprintf("https://github.com/%s/%s.git", self::getRepositoryInfoByName($repositoryName)->getUsername(), $repositoryName);
    }

    public static function setRemoteOriginUrl(string $remoteOriginUrl, string $workingDir = null, bool $isCheckResult = false): void
    {
        $commandsToCheckResult = $isCheckResult ? [GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND] : [];
        (new Process(
            "GitHub Set Remote Origin Url",
            $workingDir ?? DirHelper::getWorkingDir(),
            array_merge([
                sprintf("git remote set-url origin %s", $remoteOriginUrl)
            ], $commandsToCheckResult)
        ))->execMultiInWorkDir()->printOutput();
    }

    public static function getBranchUsingCommand(string $workDir): ?string
    {
        $process = (new Process(__FUNCTION__, $workDir, [
            GitHubEnum::GET_BRANCH_COMMAND
        ]))->execMultiInWorkDir()
            ->printOutput();
        return $process->getOutput() ? $process->getOutput()[count($process->getOutput()) - 1] : null;
    }

    /**
     * checking git already exist in this directory / folder
     * @param string $dirToCheck
     * @return bool
     */
    public static function isGit(string $dirToCheck): bool
    {
        return is_dir(sprintf("%s/.git", $dirToCheck));
    }

    public static function getRepositoryDirCommand(): string
    {
        return exec(GitHubEnum::GET_REPOSITORY_DIR_COMMAND);
    }

    /**
     * require envs: GITHUB_PERSONAL_ACCESS_TOKEN
     */
    public static function handleCachesAndGit(array $argv)
    {
        // === param ===
        $param2 = $argv[2] ?? null;
        $param3 = $argv[3] ?? null;
        // === validate ===
        //    validate env vars
        $repository = $param2 ?? getenv('REPOSITORY');
        $branch = $param3 ?? getenv('BRANCH');
        if ($repository === 'engage-api-deploy') {
            $branch = $param3 ?? getenv('API_DEPLOY_BRANCH');
        }
        $engagePlusCachesDir = getenv('ENGAGEPLUS_CACHES_DIR');
        $GitHubPersonalAccessToken = getenv('GITHUB_PERSONAL_ACCESS_TOKEN');

        if (!$repository || !$branch || !$engagePlusCachesDir || !$GitHubPersonalAccessToken) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->print("missing a REPOSITORY or BRANCH or ENGAGEPLUS_CACHES_DIR or GITHUB_PERSONAL_ACCESS_TOKEN");
            exit(); // END
        }

        $EngagePlusCachesRepositoryDir = sprintf("%s/%s", $engagePlusCachesDir, $repository);
        //     message validate
        self::LineTag($param2 ? 'CUSTOM' : 'ENV')->print("REPOSITORY = %s", $repository)
            ->setTag($param3 ? 'CUSTOM' : 'ENV')->print("BRANCH = %s", $branch)
            ->print("DIR = '$EngagePlusCachesRepositoryDir'");

        // === handle ===
        self::LineTag(TagEnum::GIT)->printTitle("Handle Caches and Git");
        //     case checkout
        if (is_dir(sprintf("%s/.git", $EngagePlusCachesRepositoryDir))) {
            self::LineNew()->print("The directory '$EngagePlusCachesRepositoryDir' exist, SKIP to handle git repository");
            //
            // case clone
        } else {
            self::LineTag(TagEnum::ERROR)->print("The directory '$EngagePlusCachesRepositoryDir' does not exist, clone new repository");
            //
            (new Process("Remove old directory", null, [
                sprintf("rm -rf \"%s\"", $EngagePlusCachesRepositoryDir),
                sprintf("mkdir -p \"%s\"", $EngagePlusCachesRepositoryDir),
            ]))->execMulti()->printOutput();
            //
            (new Process("CLONE SOURCE CODE", $EngagePlusCachesRepositoryDir, [
                sprintf("git clone -b %s %s .", $branch, self::getRemoteOriginUrl_Custom($repository, $GitHubPersonalAccessToken)),
            ]))->execMultiInWorkDir(true)->printOutput();
        }
        // === update new code ===
        (new Process("UPDATE SOURCE CODE", $EngagePlusCachesRepositoryDir, [
            sprintf("git remote set-url origin %s", self::getRemoteOriginUrl_Custom($repository, $GitHubPersonalAccessToken)),
            GitHubEnum::RESET_BRANCH_COMMAND,
            sprintf("git checkout %s", $branch),
            GitHubEnum::PULL_COMMAND
        ]))->execMultiInWorkDir()->printOutput();
        // === remove token ===
        self::setRemoteOriginUrl(self::getRemoteOriginUrl_Custom($repository), $EngagePlusCachesRepositoryDir, true);
    }

    public static function forceCheckout()
    {
        self::LineNew()->printTitle("Force checkout a GitHub repository with specific branch");
        // === input ===
        $GIT_URL_WITH_TOKEN = readline("Please input GIT_URL_WITH_TOKEN? ");
        if (!$GIT_URL_WITH_TOKEN) {
            self::LineTag(TagEnum::ERROR)->print("GitHub repository url with Token should be string");
            exit(); // END
        }
        $BRANCH_TO_FORCE_CHECKOUT = readline("Please input BRANCH_TO_FORCE_CHECKOUT? ");
        if (!$BRANCH_TO_FORCE_CHECKOUT) {
            self::LineTag(TagEnum::ERROR)->print("branch to force checkout should be string");
            exit(); // END
        }
        // === validation ===
        if (!(StrHelper::contains($GIT_URL_WITH_TOKEN, 'https://')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '@github.com')
            && StrHelper::contains($GIT_URL_WITH_TOKEN, '.git')
        )) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::FORMAT])
                ->print("invalid GitHub repository url with Token format, should be 'https://TOKEN_TOKEN@@github.com/USER_NAME/REPOSITORY.git'");
            exit(); // END
        }
        // === handle ===
        $initGitCommands = self::isGit(DirHelper::getWorkingDir()) ? [] : [GitHubEnum::INIT_REPOSITORY_COMMAND];
        $setRemoteOriginUrlCommand = exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)
            ? sprintf("git remote set-url origin %s", $GIT_URL_WITH_TOKEN)
            : sprintf("git remote add origin %s", $GIT_URL_WITH_TOKEN);
        (new Process("Set repository remote url and force checkout branch", DirHelper::getWorkingDir(), array_merge($initGitCommands, [
            $setRemoteOriginUrlCommand,
            GitHubEnum::PULL_COMMAND,
            GitHubEnum::RESET_BRANCH_COMMAND,
            sprintf("git checkout -f %s", $BRANCH_TO_FORCE_CHECKOUT),
            GitHubEnum::PULL_COMMAND,
        ])))->execMultiInWorkDir(true)->printOutput();
        // === validate result ===
        (new Process("Validate branch", DirHelper::getWorkingDir(), [
            GitHubEnum::GET_BRANCH_COMMAND
        ]))->execMultiInWorkDir()->printOutput();
    }

    /**
     * [GitHub Actions]
     * build strategy:
     *  #1 20 minutes per project, 8 projects x 20 ~ 3 hours per command : not good | run weekly
     *  #2 run every 3 minutes, 1 project, rebuild after 7 days | run every 30 minutes | need saved data
     * steps:
     *    1. get token from Secret (require aws credential)
     *    2. login gh with token
     *    3. run workflow
     * @return void
     */
    public static function buildAllProject()
    {
        $branchToBuild = GitHubEnum::DEVELOP;
        self::LineNew()->printTitle("Build all projects to keep the GitHub runner token connecting (develop env)");
        // validate
        $GitHubToken = AWSHelper::getValueEnvOpsSecretManager('GITHUB_PERSONAL_ACCESS_TOKEN');
        if (!$GitHubToken) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])->print("GitHub token not found (in Secret Manager)");
            return; //END
        }
        // handle
        //    notify
        SlackService::sendMessageInternalUsing(sprintf("[BEGIN] %s", CommandEnum::SUPPORT_COMMANDS()[CommandEnum::BUILD_ALL_PROJECTS][0]), DirHelper::getProjectDirName(), $branchToBuild);
        //    get GitHub token and login gh
        self::LineNew()->printSubTitle("login gh (GitHub CLI)");
        (new Process("login gh (GitHub CLI)", DirHelper::getWorkingDir(), [
            sprintf("echo %s | gh auth login --with-token", $GitHubToken),
        ]))->execMultiInWorkDir();
        //    send command to build all projects
        self::LineNew()->printSubTitle("send command to build all projects");
        $workspaceDir = str_replace("/" . basename($_SERVER['PWD']), '', $_SERVER['PWD']);
        self::LineNew()->print("WORKSPACE DIR = $workspaceDir");
        /** @var GitHubRepositoryInfo $repoInfo */
        foreach (GitHubEnum::GET_REPOSITORIES_INFO() as $repoInfo) {
            $repoInfo->setCurrentWorkspaceDir($workspaceDir)->setCurrentBranch($branchToBuild);
            if (is_dir($repoInfo->getCurrentRepositoryDir())) {
                // show info
                self::LineIcon(IconEnum::PLUS)->print("Project '%s > %s' | %s | %s",
                    $repoInfo->getUsername(), $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch(),
                    $repoInfo->isGitHubAction() ? "Actions workflow ✔" : "no setup X"
                );
                // handle send command to build
                if ($repoInfo->isGitHubAction()) {
                    (new Process("build project " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
                        sprintf("cd '%s'", $repoInfo->getCurrentRepositoryDir()),
                        sprintf('gh workflow run workflow--%s--%s -r %s', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch(), $repoInfo->getCurrentBranch())
                    ]))->execMultiInWorkDir();
                    // check completed
                    $startTime = new DateTime();
                    $lastSendingMinute = 0;
                    sleep(30); // wait A seconds while Actions handling new workflow
                    while (self::isActionsWorkflowQueuedOrInProgress($repoInfo)) {
                        $duration = new Duration($startTime->diff(new DateTime()));
                        $message = sprintf("Project build in progress (%s) ...", $duration->getText());
                        self::LineIcon(IconEnum::DOT)->setIndentLevel(IndentLevelEnum::ITEM_LINE)
                            ->print($message);
                        if ($duration->totalMinutes && $duration->totalMinutes > $lastSendingMinute && $duration->totalMinutes % 3 === 0) { // notify every A minutes
                            SlackService::sendMessageInternalUsing(sprintf("    %s %s", IconEnum::DOT, $message), $repoInfo->getRepositoryName(), $branchToBuild);
                            $lastSendingMinute =   $duration->totalMinutes;
                        }
                        sleep(30); // loop with interval = A seconds
                    }
                    self::LineIcon(IconEnum::CHECK)->setIndentLevel(IndentLevelEnum::ITEM_LINE)
                        ->print("build done");
                }
            }
        } // end loop
        //    notify
        SlackService::sendMessageInternalUsing(sprintf("[END] %s", CommandEnum::SUPPORT_COMMANDS()[CommandEnum::BUILD_ALL_PROJECTS][0]), DirHelper::getProjectDirName(), $branchToBuild);
    }


    private static function isActionsWorkflowQueuedOrInProgress(GitHubRepositoryInfo $repoInfo): bool
    {
        // in progress
        $resultInProgress = (new Process("check status of Actions workflow " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
            sprintf("cd '%s'", $repoInfo->getCurrentRepositoryDir()),
            sprintf('gh run list --workflow workflow--%s--%s.yml --status in_progress --json workflowName,status', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch())
        ]))->execMultiInWorkDirAndGetOutputStr();
        // queue
        $resultQueued = (new Process("check status of Actions workflow " . $repoInfo->getRepositoryName(), DirHelper::getWorkingDir(), [
            sprintf("cd '%s'", $repoInfo->getCurrentRepositoryDir()),
            sprintf('gh run list --workflow workflow--%s--%s.yml --status queued --json workflowName,status', $repoInfo->getRepositoryName(), $repoInfo->getCurrentBranch())
        ]))->execMultiInWorkDirAndGetOutputStr();
        //
        return count(json_decode($resultInProgress, true)) || count(json_decode($resultQueued, true));
    }
}

// [REMOVED] namespace App\Helpers;

// [REMOVED] use App\OpsApp;
// [REMOVED] use App\Classes\Process;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Services\SlackService;
// [REMOVED] use App\Traits\ConsoleUITrait;
// [REMOVED] use DateTime;
// [REMOVED] use Exception;

/**
 * this is an AWS Helper
 */
class AWSHelper
{
    use ConsoleUITrait;

    const ELB_TEMP_DIR = "tmp/elb-version";
    const ELB_EBEXTENSIONS_DIR = ".ebextensions"; // should place at inside elb version dir
    const ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME = "blockdevice-xvdcz.config";
    const ELB_DOCKERRUN_FILE_NAME = "Dockerrun.aws.json";
    const ELB_LOG_UPDATE_SUCCESSFULLY = "Environment update completed successfully.";
    const ELB_LOG_UPDATE_FAILED = "Failed to deploy application.";

    /**
     * save to .env file or custom name
     * @return void
     */
    public static function getSecretEnv(string $secretName, string $customENVName = null)
    {
        $ENVName = $customENVName ?? '.env'; // default
        // remove old file
        if (is_file(DirHelper::getWorkingDir($ENVName))) {
            (new Process("Delete old env file", DirHelper::getWorkingDir(), [
                sprintf("rm -f %s", DirHelper::getWorkingDir($ENVName)),
            ]))
                ->execMultiInWorkDir()->printOutput();
        }
        // get
        exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output text  > %s", $secretName, $ENVName));
        // validate result
        $isSuccess = is_file(DirHelper::getWorkingDir($ENVName)) && trim(file_get_contents(DirHelper::getWorkingDir($ENVName)));
        self::LineNew()->printCondition($isSuccess,
            "get secret '$secretName' successfully and save at '$ENVName'",
            "get secret '$secretName' failed"
        );
        if (!$isSuccess) exit(1);
    }

    /**
     * should run with command in shell:
     *      val "$(php ~/ops-app load-env-ops)"
     *
     * @return string
     */
    public static function loadOpsEnvAndHandleMore(): string
    {
        $opsEnvSecretName = 'env-ops';
        $opsEnvData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $opsEnvSecretName)));
        //
        return sprintf("#!/bin/bash\n%s\n%s", $opsEnvData, OpsApp::getShellData());
    }

    /**
     * required:
     * - data store in SecretManager > env-ops > field GITHUB_PERSONAL_ACCESS_TOKEN
     * - AWS credential have permission to get env-ops
     * @param string $fieldName
     * @return string|null
     */
    public static function getValueEnvOpsSecretManager(string $fieldName): ?string
    {
        $opsEnvSecretName = 'env-ops';
        $opsEnvData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $opsEnvSecretName)));
        //
        $opsEnvDataArr = explode(PHP_EOL, $opsEnvData);
        $line = array_filter($opsEnvDataArr, function ($item) use ($fieldName) {
            return StrHelper::contains($item, $fieldName);
        });
        return trim(str_replace("export $fieldName=", '', reset($line)), "'\"");
    }

    /**
     * works:
     * - get tags name from SSM
     * - build a version file (.zip)
     * - upload a version file to S3 bucket
     * - update ELB environment with new version
     * @return void
     */
    public static function ELBUpdateVersion()
    {
        try {
            // === validate ===
            if (!OPSHelper::validateEnvVars([
                'BRANCH', "REPOSITORY",
                'ENV', 'ECR_REPO_API', 'S3_EB_APP_VERSION_BUCKET_NAME',
                'EB_APP_VERSION_FOLDER_NAME', 'EB_ENVIRONMENT_NAME',
                'EB_2ND_DISK_SIZE',
                'EB_MAIL_CATCHER_PORT', // maybe remove after email-serivce
            ])) {
                exit(1); // END
            }
            // === handle ===
            self::LineNew()->printSeparatorLine()
                ->setTagMultiple([getenv('REPOSITORY'), getenv('BRANCH')])
                ->printTitle("Handle ELB version - ELASTIC BEANSTALK");
            //    vars
            $ENV = getenv('ENV');
            //    handle ELB version dir
            if (is_dir(DirHelper::getWorkingDir(self::ELB_TEMP_DIR))) {
                $commands[] = sprintf("rm -rf '%s'", DirHelper::getWorkingDir(self::ELB_TEMP_DIR));
            }
            $commands[] = sprintf("mkdir -p '%s/%s'", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), self::ELB_EBEXTENSIONS_DIR);
            (new Process("handle ELB version directory", DirHelper::getWorkingDir(), $commands))
                ->execMultiInWorkDir()->printOutput();
            //   handle SSM and get image tag values
            //        SSM tag names
            $SSM_ENV_TAG_API_NAME = "/$ENV/TAG_API_NAME";
            $SSM_ENV_TAG_INVOICE_SERVICE_NAME = "/$ENV/TAG_INVOICE_SERVICE_NAME";
            $SSM_ENV_TAG_PAYMENT_SERVICE_NAME = "/$ENV/TAG_PAYMENT_SERVICE_NAME";
            $SSM_ENV_TAG_INTEGRATION_API_NAME = "/$ENV/TAG_INTEGRATION_API_NAME";
            $imageTagValues = (new Process("get image tag value from AWS SSM", DirHelper::getWorkingDir(), [
                "aws ssm get-parameters --names '$SSM_ENV_TAG_API_NAME' '$SSM_ENV_TAG_INVOICE_SERVICE_NAME' '$SSM_ENV_TAG_PAYMENT_SERVICE_NAME' '$SSM_ENV_TAG_INTEGRATION_API_NAME' --output json"
            ]))->execMulti()->getOutputStrAll();
            foreach (json_decode($imageTagValues, true)['Parameters'] as $paramObj) {
                switch ($paramObj['Name']) {
                    case $SSM_ENV_TAG_API_NAME:
                        $TAG_API_NAME = $paramObj['Value'];
                        break;
                    case $SSM_ENV_TAG_INVOICE_SERVICE_NAME:
                        $TAG_INVOICE_SERVICE_NAME = $paramObj['Value'];
                        break;
                    case $SSM_ENV_TAG_PAYMENT_SERVICE_NAME:
                        $TAG_PAYMENT_SERVICE_NAME = $paramObj['Value'];
                        break;
                    case $SSM_ENV_TAG_INTEGRATION_API_NAME:
                        $TAG_INTEGRATION_API_NAME = $paramObj['Value'];
                        break;
                    default:
                        // do nothing
                        break;
                }
            }
            //   handle Dockerrun.aws.json content
            $DockerrunContent = str_replace(
                [
                    "_MAIL_CATCHER_PORT_",
                    "ECR_REPO_IMAGE_URI_API", "ECR_REPO_IMAGE_URI_INVOICE_SERVICE",
                    "ECR_REPO_IMAGE_URI_PAYMENT_SERVICE", "ECR_REPO_IMAGE_URI_INTEGRATION_API"
                ],
                [
                    getenv('EB_MAIL_CATCHER_PORT'),
                    sprintf("%s:%s", getenv('ECR_REPO_API'), $TAG_API_NAME),
                    sprintf("%s:%s", getenv('ECR_REPO_INVOICE_SERVICE'), $TAG_INVOICE_SERVICE_NAME),
                    sprintf("%s:%s", getenv('ECR_REPO_PAYMENT_SERVICE'), $TAG_PAYMENT_SERVICE_NAME),
                    sprintf("%s:%s", getenv('ECR_REPO_INTEGRATION_API'), $TAG_INTEGRATION_API_NAME)
                ],
                OpsApp::getELBTemplate()["DockerrunTemplate"]
            );
            //    write files
            file_put_contents(
                sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME),
                str_replace("_2ND_DISK_SIZE_", getenv('EB_2ND_DISK_SIZE'), OpsApp::getELBTemplate()["blockdeviceTemplate"])
            );
            file_put_contents(sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME), $DockerrunContent);
            //    validate configs files again
            //        .ebextensions/blockdevice-xvdcz.config
            $blockdeviceConfigContent = file_get_contents(sprintf("%s/%s/%s", self::ELB_TEMP_DIR, self::ELB_EBEXTENSIONS_DIR, self::ELB_EBEXTENSIONS_BLOCKDEVICE_FILE_NAME));
            self::LineNew()->print(".ebextensions/blockdevice-xvdcz.config")->print($blockdeviceConfigContent);
            if (!StrHelper::contains($blockdeviceConfigContent, getenv('EB_2ND_DISK_SIZE'))) {
                self::LineTag(TagEnum::ERROR)->print(".ebextensions/blockdevice-xvdcz.config got an error");
                exit(1); // END
            }
            //        Dockerrun.aws.json
            $DockerrunContentToCheckAgain = file_get_contents(sprintf("%s/%s", self::ELB_TEMP_DIR, self::ELB_DOCKERRUN_FILE_NAME));
            self::LineNew()->print("Dockerrun.aws.json")->print($DockerrunContentToCheckAgain);
            if (!StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_API'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_API_NAME)
                || !StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_INVOICE_SERVICE'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_INVOICE_SERVICE_NAME)
                || !StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_PAYMENT_SERVICE'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_PAYMENT_SERVICE_NAME)
                || !StrHelper::contains($DockerrunContentToCheckAgain, getenv('ECR_REPO_INTEGRATION_API'))
                || !StrHelper::contains($DockerrunContentToCheckAgain, $TAG_INTEGRATION_API_NAME)
            ) {
                self::LineTag(TagEnum::ERROR)->print("Dockerrun.aws.json got an error");
                exit(1); // END
            }
            //    create ELB version and update
            $EB_APP_VERSION_LABEL = sprintf("$ENV-$TAG_API_NAME-$TAG_INVOICE_SERVICE_NAME-$TAG_PAYMENT_SERVICE_NAME-$TAG_INTEGRATION_API_NAME-%sZ", (new DateTime())->format('Ymd-His'));
            (new Process("zip ELB version", DirHelper::getWorkingDir(self::ELB_TEMP_DIR), [
                //    create .zip file
                sprintf("zip -r %s.zip Dockerrun.aws.json .ebextensions", $EB_APP_VERSION_LABEL),
                //    Copy to s3 and create eb application version | required to run in elb-version directory
                sprintf("aws s3 cp %s.zip s3://%s/%s/%s.zip || exit 1",
                    $EB_APP_VERSION_LABEL,
                    getenv('S3_EB_APP_VERSION_BUCKET_NAME'),
                    getenv('EB_APP_VERSION_FOLDER_NAME'),
                    $EB_APP_VERSION_LABEL
                ),
                //    create ELB application version
                sprintf("aws elasticbeanstalk create-application-version --application-name %s --version-label %s --source-bundle S3Bucket=%s,S3Key=%s/%s.zip > /dev/null || exit 1",
                    getenv('EB_APP_NAME'),
                    $EB_APP_VERSION_LABEL,
                    getenv('S3_EB_APP_VERSION_BUCKET_NAME'),
                    getenv('EB_APP_VERSION_FOLDER_NAME'),
                    $EB_APP_VERSION_LABEL
                ), // > /dev/null : disabled output
                //    update EB environment
                sprintf("aws elasticbeanstalk update-environment --environment-name %s --version-label %s > /dev/null",
                    getenv('EB_ENVIRONMENT_NAME'),
                    $EB_APP_VERSION_LABEL
                ), // > /dev/null : disabled output
            ]))->execMultiInWorkDir()->printOutput();
            //    Check new service healthy every X seconds | timeout = 20 minutes
            //        08/28/2023: Elastic Beanstalk environment update about 4 - 7 minutes
            for ($minute = 3; $minute >= 1; $minute--) {
                self::LineNew()->print("Wait $minute minutes for the ELB environment does the update, and add some lines of logs...");
                sleep(60);
            }
            //        do check | ELB logs
            for ($i = 1; $i <= 40; $i++) {
                self::LineNew()->print("Healthcheck the $i time");
                $lastELBLogs = (new Process("get last ELB logs", DirHelper::getWorkingDir(), [
                    sprintf("aws elasticbeanstalk describe-events --application-name %s --environment-name %s --query 'Events[].Message' --output json --max-items 5",
                        getenv('EB_APP_NAME'),
                        getenv('EB_ENVIRONMENT_NAME')
                    ),
                ]))->execMulti()->getOutputStrAll();
                if (in_array(self::ELB_LOG_UPDATE_SUCCESSFULLY, json_decode($lastELBLogs))) {
                    self::LineTag(TagEnum::SUCCESS)->print(self::ELB_LOG_UPDATE_SUCCESSFULLY);
                    SlackService::sendMessage(['script path', 'slack', sprintf(
                        "[FINISH] [SUCCESS] %s just finished building and deploying the project %s",
                        getenv('DEVICE'), getenv('REPOSITORY')
                    )]);
                    exit(0); // END | successful
                } else if (in_array(self::ELB_LOG_UPDATE_FAILED, json_decode($lastELBLogs))) {
                    self::LineTag(TagEnum::ERROR)->print(self::ELB_LOG_UPDATE_FAILED);
                    SlackService::sendMessage(['script path', 'slack', sprintf(
                        "[FINISH] [FAILURE 1 | Deploy failed] %s just finished building and deploying the project %s",
                        getenv('DEVICE'), getenv('REPOSITORY')
                    )]);
                    exit(1); // END | failed
                } else {
                    self::LineNew()->print("Environment is still not healthy");
                    // check again after X seconds
                    sleep(30);
                }
            }
            //             case timeout
            self::LineTag(TagEnum::ERROR)->print("Deployment got a timeout result");
            SlackService::sendMessage(['script path', 'slack', sprintf(
                "[FINISH] [FAILURE 2 | Timeout] %s just finished building and deploying the project %s",
                getenv('DEVICE'), getenv('REPOSITORY')
            )]);
            exit(1); // END | failed
        } catch (Exception $ex) {
            self::LineTag(TagEnum::ERROR)->print($ex->getMessage());
            exit(1); // END | exception error
        }
    }
}

// [REMOVED] namespace App\Helpers;

// [REMOVED] use App\OpsApp;
// [REMOVED] use App\Classes\Release;
// [REMOVED] use App\Classes\Version;

class AppHelper
{
    /**
     * @param string $fullDirPath
     * @return void
     */
    public static function requireOneAllPHPFilesInDir(string $fullDirPath): void
    {
        foreach (scandir($fullDirPath) as $subDirName) {
            $fullSubDirToCheck = sprintf("%s/%s", $fullDirPath, $subDirName);
            if ($subDirName != '.' && $subDirName != '..' && is_dir($fullSubDirToCheck)) {
                $PHPFiles = glob("$fullSubDirToCheck/*.php");
                foreach ($PHPFiles as $PHPFile) {
                    require_once $PHPFile;
                }
                // check next
                AppHelper::requireOneAllPHPFilesInDir($fullSubDirToCheck);
            }
        }
    }


    /**
     * this will increase app:APP_VERSION
     * this will push new code to GitHub
     *
     * @param string $part
     * @return Version
     */
    public static function increaseVersion(string $part = Version::PATCH): Version
    {
        // handle version
        $isAddToVersionMD = false;
        switch ($part) {
            case Version::MINOR:
                $newVersion = Version::parse(OpsApp::APP_VERSION)->bump(Version::MINOR);
                $isAddToVersionMD = true;
                break;
            case Version::PATCH:
            default:
                $newVersion = Version::parse(OpsApp::APP_VERSION)->bump($part);
                break;
        }
        // update data
        //    app class
        $appClassPath = Release::GET_FILES_LIST()[count(Release::GET_FILES_LIST()) - 1];
        file_put_contents($appClassPath, preg_replace(
            '/APP_VERSION\s*=\s*\'(\d+\.\d+\.\d+)\'/',
            sprintf("APP_VERSION = '%s'", $newVersion->toString()),
            file_get_contents($appClassPath)
        ));
        //    README.MD
        $readmePath = "README.MD";
        file_put_contents($readmePath, preg_replace(
            '/ops-app v(\d+\.\d+\.\d+)/',
            sprintf("ops-app v%s", $newVersion->toString()),
            file_get_contents($readmePath)
        ));
        //    VERSION.MD
        if ($isAddToVersionMD) {
            $VersionMDPath = "VERSION.MD";
            file_put_contents($VersionMDPath, str_replace(
                sprintf("## === v%s ===", $newVersion->getMajor()),
                sprintf("## === v%s ===\n- %s | TODO ADD SOME CHANGE LOGS", $newVersion->getMajor(), $newVersion->toString()),
                file_get_contents($VersionMDPath)
            ));
        }
        //
        return $newVersion;
    }
}

// [REMOVED] namespace App\Helpers;


// [REMOVED] use App\Enum\DockerEnum;
// [REMOVED] use App\Enum\IconEnum;
// [REMOVED] use App\Enum\IndentLevelEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Enum\UIEnum;
// [REMOVED] use App\Classes\DockerImage;
// [REMOVED] use App\Classes\Process;
// [REMOVED] use App\Traits\ConsoleUITrait;

/**
 * this is a Docker helper
 */
class DockerHelper
{
    use ConsoleUITrait;

    public static function isDockerInstalled(): bool
    {
        $dockerPath = trim(exec("which docker"));
        return !(!$dockerPath || StrHelper::contains($dockerPath, 'not found'));
    }

    /**
     * keep image by repository name with specific tag, use for keep latest image
     *
     * @param array $argv
     * @return void
     */
    public static function keepImageBy(array $argv): void
    {
        // === param ===
        $imageRepository = $argv[2] ?? null;
        $imageTag = $argv[3] ?? null;
        // === validate ===
        if (!$imageRepository || !$imageTag) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->print("missing a 'imageRepository' 'imageTag'");
            exit(); // END
        }
        // === handle ===
        self::LineTag(TagEnum::DOCKER)->printTitle("Keep latest Docker image by repository");
        $listImage = self::getListImages();
        if (count($listImage) === 0) {
            self::LineNew()->print("Empty images. Do nothing.")->printSeparatorLine();
            return; // END
        }
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            self::LineIndent(IndentLevelEnum::ITEM_LINE)->printSeparatorLine();
            // case: to check image
            if ($image->getRepository() === $imageRepository) {
                if ($image->getTag() === $imageTag) {
                    self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::CHECK)
                        ->print("Keep image '%s:%s'", $image->getRepository(), $image->getTag());
                    self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)
                        ->print("(%s | %s)", $image->getCreatedSince(), $image->getSize());
                    // do nothing | skip this image
                } else {
                    self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::X)
                        ->setColor(UIEnum::COLOR_RED)
                        ->print("Delete image '%s:%s'", $image->getRepository(), $image->getTag());
                    self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)
                        ->setColor(UIEnum::COLOR_RED)
                        ->print("(%s | %s)", $image->getCreatedSince(), $image->getSize());
                    (new Process("Delete Docker Image", DirHelper::getWorkingDir(), [
                        sprintf("docker rmi -f %s", $image->getId())
                    ]))->setOutputParentIndentLevel(IndentLevelEnum::SUB_ITEM_LINE)
                        ->execMultiInWorkDir(true)->printOutput();
                }
                //
                // case: other images
            } else {
                self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::CHECK)
                    ->print("Keep other image '%s:%s'", $image->getRepository(), $image->getTag());
                self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)
                    ->print("(%s | %s)", $image->getCreatedSince(), $image->getSize());
            }
        }
        //
        self::LineNew()->printSeparatorLine();
    }

    /**
     * add ENVs into Dockerfile below FROM line. Required: DockerfilePath, secretName
     *
     * @param array $argv
     * @return void
     */
    public static function DockerfileAddEnvs(array $argv): void
    {
        // === param ===
        $DockerfilePath = $argv[2] ?? null;
        $secretName = $argv[3] ?? null;
        // === validate ===
        if (!$DockerfilePath || !$secretName) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                ->print("missing a 'DockerfilePath' 'secretName'");
            exit(); // END
        }
        if (!is_file($DockerfilePath)) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR])
                ->print("'DockerfilePath' isn't a file");
            exit(); // END
        }
        // === handle ===
        self::LineTag(TagEnum::DOCKER)->printTitle("Dockerfile: get '%s' from AWS Secret and add to Dockerfile", $secretName);
        //    get secret
        $envData = json_decode(exec(sprintf("aws secretsmanager get-secret-value --secret-id %s --query SecretString --output json", $secretName)));
        $envLines = ["", "# === Generated vars ==="];
        foreach (explode(PHP_EOL, $envData) as $line) {
            $lineTrim = trim($line);
            if ($lineTrim && strlen($lineTrim) > 0) {
                if ($lineTrim[0] !== '#') {  // skip comment line
                    $envLines[] = sprintf("%s %s", DockerEnum::ENV, $line);
                }
            }
        }
        $envLines[] = "# === end Generated vars ===";
        //    add to Docker file
        $DockerfileLines = [];
        foreach (explode(PHP_EOL, file_get_contents($DockerfilePath)) as $line2) {
            $DockerfileLines[] = $line2;
            // insert envs after FROM ... line
            if (substr($line2, 0, 4) === DockerEnum::FROM) {
                $DockerfileLines = array_merge($DockerfileLines, $envLines);
            }
        }
        file_put_contents($DockerfilePath, implode(PHP_EOL, $DockerfileLines));
        //    validate result
        if (StrHelper::contains(file_get_contents($DockerfilePath), DockerEnum::ENV)) {
            self::LineTag(TagEnum::SUCCESS)->print("get '%s' from AWS Secret and add to Dockerfile successfully", $secretName);
        } else {
            self::LineTag(TagEnum::ERROR)->print("ENV data doesn't exist in Dockerfile");
            exit(1);
        }
        self::LineNew()->printSeparatorLine();
    }

    private static function getListImages(): array
    {
        $list = [];
        $dockerImagesDataArr = (new Process("Get Docker Images Data", DirHelper::getWorkingDir(), [
            "docker images --format \"{{json .}}\" || exit 0"
        ]))->execMultiInWorkDir(true)->getOutput();
        foreach ($dockerImagesDataArr as $imageDataJson) {
            $imageData = json_decode($imageDataJson, true);
            $list[] = new DockerImage(
                $imageData['Repository'], $imageData['Tag'], $imageData['ID'],
                $imageData['CreatedAt'], $imageData['CreatedSince'], $imageData['Size']
            );
        }
        return $list;
    }

    /**
     * @return bool
     */
    public static function isDanglingImages(): bool
    {
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            if ($image->isDanglingImage()) return true;
        }
        return false;
    }

    /**
     * remove dangling image / <none> image
     * @return void
     */
    public static function removeDanglingImages(): void
    {
        // === handle ===
        self::LineTag(TagEnum::DOCKER)->printTitle("Remove dangling images / <none> images");
        $listImage = self::getListImages();
        if (count($listImage) === 0) {
            self::LineNew()->print("Empty images. Do nothing.")->printSeparatorLine();
            return; // END
        }
        /** @var DockerImage $image */
        foreach (self::getListImages() as $image) {
            if ($image->isDanglingImage()) {
                self::LineIndent(IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::X)
                    ->setColor(UIEnum::COLOR_RED)
                    ->print("Delete dangling image '%s:%s'", $image->getRepository(), $image->getTag());
                (new Process("Delete Docker Image", DirHelper::getWorkingDir(), [
                    sprintf("docker rmi -f %s", $image->getId())
                ]))->setOutputParentIndentLevel(IndentLevelEnum::SUB_ITEM_LINE)
                    ->execMultiInWorkDir(true)->printOutput();
            }
        }
        //
        self::LineNew()->printSeparatorLine();
    }
}


// [REMOVED] namespace App\Helpers;

// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Traits\ConsoleUITrait;

/**
 * this is a simple STRing helper for PHP < 8.1
 */
class StrHelper
{
    use ConsoleUITrait;

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
    public static function replaceTextInFile(array $argv)
    {
// === validate ===
//    validate a message
        $searchText = $argv[2] ?? null;
        $replaceText = $argv[3] ?? null;
        $filePath = $argv[4] ?? null;
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

// [REMOVED] namespace App\Services;

// [REMOVED] use App\Enum\GitHubEnum;
// [REMOVED] use App\Enum\TagEnum;
// [REMOVED] use App\Helpers\AWSHelper;
// [REMOVED] use App\Traits\ConsoleUITrait;

class SlackService
{
    use ConsoleUITrait;

    /**
     * select appropriate Slack channel to notify
     * - production channel: notify to the team, the manager  (required: env > SLACK_CHANNEL_PRODUCTION)
     * - develop and test channel: notify to the developer (required: env > SLACK_CHANNEL)
     * - default will return: SLACK_CHANNEL
     *
     * @return string|null
     */
    private static function selectSlackChannel(): ?string
    {
        // ops-lib | testing
        if (getenv('REPOSITORY') === 'ops-lib') {
            return getenv('SLACK_CHANNEL'); // END
        }
        // database-utils
        if (getenv('SLACK_CHANNEL_PRODUCTION') && getenv('REPOSITORY') === 'engage-database-utils') {
            return getenv('SLACK_CHANNEL_PRODUCTION'); // END
        }
        // master branches
        if (getenv('SLACK_CHANNEL_PRODUCTION') && getenv('BRANCH') === GitHubEnum::MASTER) {
            return getenv('SLACK_CHANNEL_PRODUCTION'); // END
        }
        //    in case don't config SLACK_CHANNEL_PRODUCTION, will fall to default below here for master branch
        // branches: staging, develop, ticket's branches
        return getenv('SLACK_CHANNEL'); // END
    }

    /**
     * @param array $argv
     * @return void
     */
    public static function sendMessage(array $argv)
    {
        // === validate ===
        //    validate a message
        $message = $argv[2] ?? null;
        if (!$message) {
            self::LineTag(TagEnum::ERROR)->print("missing a MESSAGE");
            exit(); // END
        }
        //    validate env vars
        $repository = getenv('REPOSITORY');
        $branch = getenv('BRANCH');
        $slackBotToken = getenv('SLACK_BOT_TOKEN');
        $slackChannel = self::selectSlackChannel();
        if (!$repository || !$branch || !$slackBotToken || !$slackChannel) {
            self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::ENV])
                ->print("missing a BRANCH or REPOSITORY or SLACK_BOT_TOKEN or SLACK_CHANNEL");
            exit(); // END
        }

        // === handle ===
        $slackUrl = "https://slack.com/api/chat.postMessage";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $slackUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [sprintf("Authorization: Bearer %s", $slackBotToken)]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            "channel" => $slackChannel,
            "text" => sprintf("[%s] [%s] > %s", $repository, $branch, $message),
        ]));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // Suppress output
        $response = curl_exec($curl);
        if (!$response) {
            self::LineTag(TagEnum::ERROR)->print(curl_error($curl));
        } else {
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($responseCode === 200) {
                if (json_decode($response, true)['ok']) {
                    self::LineTagMultiple([TagEnum::SLACK, TagEnum::SUCCESS])->print("Message sent successfully | Slack status OK | HTTP code $responseCode");
                } else {
                    self::LineTagMultiple([TagEnum::SLACK, TagEnum::ERROR])->print(json_decode($response, true)['error'] . " | Slack status NO | HTTP code $responseCode");
                }
            } else {
                self::LineTagMultiple([TagEnum::SLACK, TagEnum::ERROR])->print("Error sending message | HTTP code $responseCode");
            }
        }
    }

    /**
     * required AWS credential have access to env-ops (Secret Manager)
     * use internal lib
     * @return void
     */
    public static function sendMessageInternalUsing(
        string $message,
        string $repository = 'unknown_repository',
        string $branch = 'unknown_branch'
    )
    {
        // handle
        //    prepare envs
        putenv('REPOSITORY=' . $repository);
        putenv('BRANCH=' . $branch);
        putenv('SLACK_BOT_TOKEN=' . AWSHelper::getValueEnvOpsSecretManager('SLACK_BOT_TOKEN'));
        putenv('SLACK_CHANNEL=' . AWSHelper::getValueEnvOpsSecretManager('SLACK_CHANNEL'));
        self::sendMessage(['script path', 'slack', $message]);
    }
}

// [REMOVED] namespace App\Traits;

// [REMOVED] use App\Classes\TextLine;
// [REMOVED] use App\Enum\IndentLevelEnum;

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

    // === colors ===
    private static function color(string $text, int $color): string
    {
        return sprintf("\033[%dm%s\033[0m", $color, $text);
    }
    private static function colorFormat(string $text, int $color, int $format): string
    {
        return sprintf("\033[%d;%dm%s\033[0m", $color, $format, $text);
    }
}

// === end Generated libraries classes ===


// === Generated app class ===

class OpsApp
{
    use ConsoleUITrait;

    const APP_NAME = 'OPS APP (PHP)';
    const APP_VERSION = '3.2.17';
    const APP_MAIN_COMMAND = 'ops-app';

    const SHELL_DATA_BASE_64 = 'IyA9PT0gUkVRVUlSRUQ6IGdldCBlbnYtb3BzIGFuZCBhcHBlbmQgdG8gdGhpcyBmaWxlCgojID09PSBsb2FkIFJlcG9zaXRvcnkgSW5mbyA9PT0KZXhwb3J0IEJSQU5DSD0kKHBocCB+L29wcy1hcHAgYnJhbmNoKQpleHBvcnQgUkVQT1NJVE9SWT0kKHBocCB+L29wcy1hcHAgcmVwb3NpdG9yeSkKZXhwb3J0IEhFQURfQ09NTUlUX0lEPSQocGhwIH4vb3BzLWFwcCBoZWFkLWNvbW1pdC1pZCkKIyA9PT0gRU5EID09PQoKIyA9PT0gY29uc3RhbnRzID09PQpleHBvcnQgRE9DS0VSX0JBU0VfVEFHX1BST0RVQ1RJT049InByb2R1Y3Rpb24iCmV4cG9ydCBET0NLRVJfQkFTRV9UQUdfREVWRUxPUD0iZGV2ZWxvcCIKIyAgICBXQVJOSU5HOiBkZWxldGUgJ2F1dGguanNvbicgYWZ0ZXIgdXNlIHRoaXMgY29tbWFuZCAnQ09NUE9TRVJfQ09ORklHX0dJVEhVQl9UT0tFTicKZXhwb3J0IENPTVBPU0VSX0NPTkZJR19HSVRIVUJfVE9LRU49ImNvbXBvc2VyIGNvbmZpZyBnaXRodWItb2F1dGguZ2l0aHViLmNvbSAke0dJVEhVQl9QRVJTT05BTF9BQ0NFU1NfVE9LRU59IgpleHBvcnQgQ09NUE9TRVJfQ09ORklHX0FMTE9XX1BMVUdJTlNfU1lNRk9OWV9GTEVYPSJjb21wb3NlciBjb25maWcgLS1uby1wbHVnaW5zIGFsbG93LXBsdWdpbnMuc3ltZm9ueS9mbGV4IHRydWUiCmV4cG9ydCBDT01QT1NFUl9JTlNUQUxMX0RFVkVMT1A9ImNvbXBvc2VyIGluc3RhbGwiCmV4cG9ydCBDT01QT1NFUl9JTlNUQUxMX0RFVkVMT1BfVE9fQlVJTERfQ0FDSEVTPSJjb21wb3NlciBpbnN0YWxsIC0tbm8tYXV0b2xvYWRlciAtLW5vLXNjcmlwdHMgLS1uby1wbHVnaW5zIgpleHBvcnQgQ09NUE9TRVJfSU5TVEFMTF9QUk9EVUNUSU9OPSJjb21wb3NlciBpbnN0YWxsIC0tbm8tZGV2IC0tb3B0aW1pemUtYXV0b2xvYWRlciIKZXhwb3J0IENPTVBPU0VSX0lOU1RBTExfUFJPRFVDVElPTl9UT19CVUlMRF9DQUNIRVM9ImNvbXBvc2VyIGluc3RhbGwgLS1uby1kZXYgLS1uby1hdXRvbG9hZGVyIC0tbm8tc2NyaXB0cyAtLW5vLXBsdWdpbnMiCgojID09PSBoYW5kbGUgYnJhbmNoZXMgdmFycyA9PT0KaWYgWyAiJHtCUkFOQ0h9IiA9ICJkZXZlbG9wIiBdOyB0aGVuCiAgZXhwb3J0IEVOVj1kZXYKICBleHBvcnQgQVBJX0RFUExPWV9CUkFOQ0g9ZGV2ZWxvcC1tdWx0aS1jb250YWluZXIKICBleHBvcnQgRUJfRU5WSVJPTk1FTlRfTkFNRT0iZGV2ZWxvcC1tdWx0aS1jb250YWluZXIiCiAgZXhwb3J0IEVCXzJORF9ESVNLX1NJWkU9IjIwIgogIGV4cG9ydCBFQl9NQUlMX0NBVENIRVJfUE9SVD0iLHsgXCJob3N0UG9ydFwiOiAxMDI1LCBcImNvbnRhaW5lclBvcnRcIjogMTAyNSB9IiAjIG1heWJlIHJlbW92ZSBhZnRlciBlbWFpbC1zZXJ2aWNlCiAgZXhwb3J0IEVOVl9VUkxfUFJFRklYPSIke0JSQU5DSH0tIgogICMKICBleHBvcnQgQ09NUE9TRVJfSU5TVEFMTD0iJHtDT01QT1NFUl9JTlNUQUxMX0RFVkVMT1B9IgogIGV4cG9ydCBET0NLRVJfQkFTRV9UQUc9IiR7RE9DS0VSX0JBU0VfVEFHX0RFVkVMT1B9IgogIGV4cG9ydCBET0NLRVJfQkFTRV9UQUdfQVBJPSIke0RPQ0tFUl9CQVNFX1RBR19ERVZFTE9QfSIgIyBtYXliZSByZW1vdmUgYWZ0ZXIgZW1haWwtc2VydmljZQogICMKICBleHBvcnQgRU1BSUxfU0VSVklDRV9FWFRFUk5BTF9QT1JUPTEwMDAwCiAgZXhwb3J0IEVNQUlMX1NFUlZJQ0VfQ09OVEFJTkVSX1BPUlQ9ODAKZmkKaWYgWyAiJHtCUkFOQ0h9IiA9ICJzdGFnaW5nIiBdOyB0aGVuCiAgZXhwb3J0IEVOVj1zdGcKICBleHBvcnQgQVBJX0RFUExPWV9CUkFOQ0g9c3RhZ2luZy1tdWx0aS1jb250YWluZXIKICBleHBvcnQgRUJfRU5WSVJPTk1FTlRfTkFNRT0ic3RhZ2luZy1tdWx0aS1jb250YWluZXIiCiAgZXhwb3J0IEVCXzJORF9ESVNLX1NJWkU9IjIwIgogIGV4cG9ydCBFQl9NQUlMX0NBVENIRVJfUE9SVD0iLHsgXCJob3N0UG9ydFwiOiAxMDI1LCBcImNvbnRhaW5lclBvcnRcIjogMTAyNSB9IiAjIG1heWJlIHJlbW92ZSBhZnRlciBlbWFpbC1zZXJ2aWNlCiAgZXhwb3J0IEVOVl9VUkxfUFJFRklYPSIke0JSQU5DSH0tIgogICMKICBleHBvcnQgQ09NUE9TRVJfSU5TVEFMTD0iJHtDT01QT1NFUl9JTlNUQUxMX1BST0RVQ1RJT059IgogIGV4cG9ydCBET0NLRVJfQkFTRV9UQUc9IiR7RE9DS0VSX0JBU0VfVEFHX1BST0RVQ1RJT059IgogIGV4cG9ydCBET0NLRVJfQkFTRV9UQUdfQVBJPSIke0RPQ0tFUl9CQVNFX1RBR19ERVZFTE9QfSIgIyBtYXliZSByZW1vdmUgYWZ0ZXIgZW1haWwtc2VydmljZQogICMKICBleHBvcnQgRU1BSUxfU0VSVklDRV9FWFRFUk5BTF9QT1JUPTEwMDAxCiAgZXhwb3J0IEVNQUlMX1NFUlZJQ0VfQ09OVEFJTkVSX1BPUlQ9ODAKZmkKaWYgWyAiJHtCUkFOQ0h9IiA9ICJtYXN0ZXIiIF07IHRoZW4KICBleHBvcnQgRU5WPXByZAogIGV4cG9ydCBBUElfREVQTE9ZX0JSQU5DSD1tYXN0ZXItbXVsdGktY29udGFpbmVyCiAgZXhwb3J0IEVCX0VOVklST05NRU5UX05BTUU9ImVuZ2FnZXBsdXMtcHJvZC1tdWx0aS1jb250YWluZXIiCiAgZXhwb3J0IEVCXzJORF9ESVNLX1NJWkU9IjEwMCIKICBleHBvcnQgRUJfTUFJTF9DQVRDSEVSX1BPUlQ9IiAgICAiICMgbWF5YmUgcmVtb3ZlIGFmdGVyIGVtYWlsLXNlcnZpY2UgfCA0IHNwYWNlcyB0byBwYXNzIGVtcHR5IHN0cmluZwogIGV4cG9ydCBFTlZfVVJMX1BSRUZJWD0iIgogICMKICBleHBvcnQgQ09NUE9TRVJfSU5TVEFMTD0iJHtDT01QT1NFUl9JTlNUQUxMX1BST0RVQ1RJT059IgogIGV4cG9ydCBET0NLRVJfQkFTRV9UQUc9IiR7RE9DS0VSX0JBU0VfVEFHX1BST0RVQ1RJT059IgogIGV4cG9ydCBET0NLRVJfQkFTRV9UQUdfQVBJPSIke0RPQ0tFUl9CQVNFX1RBR19QUk9EVUNUSU9OfSIgIyBtYXliZSByZW1vdmUgYWZ0ZXIgZW1haWwtc2VydmljZQogICMKICBleHBvcnQgRU1BSUxfU0VSVklDRV9FWFRFUk5BTF9QT1JUPTEwMDAyCiAgZXhwb3J0IEVNQUlMX1NFUlZJQ0VfQ09OVEFJTkVSX1BPUlQ9ODAKZmkKIyA9PT0gRU5EID09PQoKIyA9PT0gQVdTIEFjY291bnQgY29uZmlndXJhdGlvbiA9PT0KZXhwb3J0IEFXU19BQ0NPVU5UX0lEPSI5ODIwODA2NzI5ODMiCmV4cG9ydCBSRUdJT049ImFwLWVhc3QtMSIKIyAgICBFQ1IgY29uZmlndXJhdGlvbgojICAgICAgICBiYXNlIGFuZCBjYWNoZXMgcmVwb3NpdG9yaWVzCmV4cG9ydCBFQ1JfUkVQT19BUElfQkFTRT0iJHtBV1NfQUNDT1VOVF9JRH0uZGtyLmVjci4ke1JFR0lPTn0uYW1hem9uYXdzLmNvbS9lbmdhZ2VwbHVzLWJhc2UtYXBpLXJlcG9zaXRvcnkiCmV4cG9ydCBFQ1JfUkVQT19QQVlNRU5UX1NFUlZJQ0VfQkFTRT0iJHtBV1NfQUNDT1VOVF9JRH0uZGtyLmVjci4ke1JFR0lPTn0uYW1hem9uYXdzLmNvbS9lbmdhZ2VwbHVzLWJhc2UtcGF5bWVudC1zZXJ2aWNlLXJlcG9zaXRvcnkiCmV4cG9ydCBFQ1JfUkVQT19JTlZPSUNFX1NFUlZJQ0VfQkFTRT0iJHtBV1NfQUNDT1VOVF9JRH0uZGtyLmVjci4ke1JFR0lPTn0uYW1hem9uYXdzLmNvbS9lbmdhZ2VwbHVzLWJhc2UtaW52b2ljZS1zZXJ2aWNlLXJlcG9zaXRvcnkiCmV4cG9ydCBFQ1JfUkVQT19JTlRFR1JBVElPTl9BUElfQkFTRT0iJHtBV1NfQUNDT1VOVF9JRH0uZGtyLmVjci4ke1JFR0lPTn0uYW1hem9uYXdzLmNvbS9lbmdhZ2VwbHVzLWJhc2UtaW50ZWdyYXRpb24tYXBpLXJlcG9zaXRvcnkiCmV4cG9ydCBFQ1JfUkVQT19FTUFJTF9TRVJWSUNFX0JBU0U9IiR7QVdTX0FDQ09VTlRfSUR9LmRrci5lY3IuJHtSRUdJT059LmFtYXpvbmF3cy5jb20vZW5nYWdlcGx1cy1iYXNlLWVtYWlsLXNlcnZpY2UtcmVwb3NpdG9yeSIKIyAgICAgICAgbm9ybWFsIHJlcG9zaXRvcmllcwpleHBvcnQgRUNSX1JFUE9fQVBJPSIke0FXU19BQ0NPVU5UX0lEfS5ka3IuZWNyLiR7UkVHSU9OfS5hbWF6b25hd3MuY29tL2VuZ2FnZXBsdXMtJHtFTlZ9LWFwaS1yZXBvc2l0b3J5IgpleHBvcnQgRUNSX1JFUE9fUEFZTUVOVF9TRVJWSUNFPSIke0FXU19BQ0NPVU5UX0lEfS5ka3IuZWNyLiR7UkVHSU9OfS5hbWF6b25hd3MuY29tL2VuZ2FnZXBsdXMtJHtFTlZ9LXBheW1lbnQtc2VydmljZS1yZXBvc2l0b3J5IgpleHBvcnQgRUNSX1JFUE9fSU5WT0lDRV9TRVJWSUNFPSIke0FXU19BQ0NPVU5UX0lEfS5ka3IuZWNyLiR7UkVHSU9OfS5hbWF6b25hd3MuY29tL2VuZ2FnZXBsdXMtJHtFTlZ9LWludm9pY2Utc2VydmljZS1yZXBvc2l0b3J5IgpleHBvcnQgRUNSX1JFUE9fSU5URUdSQVRJT05fQVBJPSIke0FXU19BQ0NPVU5UX0lEfS5ka3IuZWNyLiR7UkVHSU9OfS5hbWF6b25hd3MuY29tL2VuZ2FnZXBsdXMtJHtFTlZ9LWludGVncmF0aW9uLWFwaS1yZXBvc2l0b3J5IgpleHBvcnQgRUNSX1JFUE9fRU1BSUxfU0VSVklDRT0iJHtBV1NfQUNDT1VOVF9JRH0uZGtyLmVjci4ke1JFR0lPTn0uYW1hem9uYXdzLmNvbS9lbmdhZ2VwbHVzLSR7RU5WfS1lbWFpbC1zZXJ2aWNlLXJlcG9zaXRvcnkiCiMgICAgRWxhc3RpYyBCZWFuc3RhbGsgY29uZmlndXJhdGlvbgpleHBvcnQgUzNfRUJfQVBQX1ZFUlNJT05fQlVDS0VUX05BTUU9ImVsYXN0aWNiZWFuc3RhbGstJHtSRUdJT059LSR7QVdTX0FDQ09VTlRfSUR9IgpleHBvcnQgRUJfQVBQX1ZFUlNJT05fRk9MREVSX05BTUU9ImVuZ2FnZXBsdXMiCmV4cG9ydCBFQl9BUFBfTkFNRT0iZW5nYWdlcGx1cyIKIyA9PT0gRU5EID09PQoKIyA9PT0gRW5nYWdlUGx1cyBjb25maWd1cmF0aW9uID09PQpleHBvcnQgRU5HQUdFUExVU19DQUNIRVNfRk9MREVSPSIuY2FjaGVzX2VuZ2FnZXBsdXMiCmV4cG9ydCBFTkdBR0VQTFVTX0NBQ0hFU19ESVI9IiQocGhwIH4vb3BzLWFwcCBob21lLWRpcikvJHtFTkdBR0VQTFVTX0NBQ0hFU19GT0xERVJ9IgpleHBvcnQgRU5HQUdFUExVU19DQUNIRVNfUkVQT1NJVE9SWV9ESVI9IiR7RU5HQUdFUExVU19DQUNIRVNfRElSfS8ke1JFUE9TSVRPUll9IgojID09PSBFTkQgPT09CgojID09PSBnZXQgREVWSUNFIGZyb20gcGFyYW0gMSA9PT0KZXhwb3J0IERFVklDRT0iJDEiCiMgPT09IEVORCA9PT0K';

    public static function getShellData()
    {
        return self::SHELL_DATA_BASE_64
            ? base64_decode(self::SHELL_DATA_BASE_64)
            : file_get_contents('app/_shell_/handle-env-ops.sh');
    }

    const ELB_TEMPLATE_BASE_64 = 'eyJibG9ja2RldmljZVRlbXBsYXRlIjoib3B0aW9uX3NldHRpbmdzOlxuICBhd3M6YXV0b3NjYWxpbmc6bGF1bmNoY29uZmlndXJhdGlvbjpcbiAgICBCbG9ja0RldmljZU1hcHBpbmdzOiBcL2RldlwveHZkY3o9Ol8yTkRfRElTS19TSVpFXzp0cnVlOmdwMlxuIiwiRG9ja2VycnVuVGVtcGxhdGUiOiJ7XG4gIFwiQVdTRUJEb2NrZXJydW5WZXJzaW9uXCI6IDIsXG4gIFwiY29udGFpbmVyRGVmaW5pdGlvbnNcIjogW1xuICAgIHtcbiAgICAgIFwibmFtZVwiOiBcImFwaVwiLFxuICAgICAgXCJpbWFnZVwiOiBcIkVDUl9SRVBPX0lNQUdFX1VSSV9BUElcIixcbiAgICAgIFwiZXNzZW50aWFsXCI6IHRydWUsXG4gICAgICBcIm1lbW9yeVJlc2VydmF0aW9uXCI6IDI1NixcbiAgICAgIFwicG9ydE1hcHBpbmdzXCI6IFtcbiAgICAgICAge1xuICAgICAgICAgIFwiaG9zdFBvcnRcIjogODAsXG4gICAgICAgICAgXCJjb250YWluZXJQb3J0XCI6IDgwODBcbiAgICAgICAgfVxuICAgICAgICBfTUFJTF9DQVRDSEVSX1BPUlRfXG4gICAgICBdLFxuICAgICAgXCJsaW5rc1wiOiBbXG4gICAgICAgIFwicGF5bWVudC1zZXJ2aWNlXCIsXG4gICAgICAgIFwiaW52b2ljZS1zZXJ2aWNlXCIsXG4gICAgICAgIFwiaW50ZWdyYXRpb24tYXBpXCJcbiAgICAgIF1cbiAgICB9LFxuICAgIHtcbiAgICAgIFwibmFtZVwiOiBcImludm9pY2Utc2VydmljZVwiLFxuICAgICAgXCJpbWFnZVwiOiBcIkVDUl9SRVBPX0lNQUdFX1VSSV9JTlZPSUNFX1NFUlZJQ0VcIixcbiAgICAgIFwibWVtb3J5UmVzZXJ2YXRpb25cIjogMjU2LFxuICAgICAgXCJlc3NlbnRpYWxcIjogZmFsc2VcbiAgICB9LFxuICAgIHtcbiAgICAgIFwibmFtZVwiOiBcInBheW1lbnQtc2VydmljZVwiLFxuICAgICAgXCJpbWFnZVwiOiBcIkVDUl9SRVBPX0lNQUdFX1VSSV9QQVlNRU5UX1NFUlZJQ0VcIixcbiAgICAgIFwibWVtb3J5UmVzZXJ2YXRpb25cIjogMjU2LFxuICAgICAgXCJlc3NlbnRpYWxcIjogZmFsc2VcbiAgICB9LFxuICAgIHtcbiAgICAgIFwibmFtZVwiOiBcImludGVncmF0aW9uLWFwaVwiLFxuICAgICAgXCJpbWFnZVwiOiBcIkVDUl9SRVBPX0lNQUdFX1VSSV9JTlRFR1JBVElPTl9BUElcIixcbiAgICAgIFwibWVtb3J5UmVzZXJ2YXRpb25cIjogMjU2LFxuICAgICAgXCJlc3NlbnRpYWxcIjogZmFsc2VcbiAgICB9XG4gIF1cbn1cbiJ9';

    public static function getELBTemplate()
    {
        return self::ELB_TEMPLATE_BASE_64
            ? json_decode(base64_decode(self::ELB_TEMPLATE_BASE_64), true)
            : [
                'blockdeviceTemplate' => file_get_contents('app/_AWS_/ELB-template/.ebextensions/blockdevice-xvdcz.config.TEMPLATE'),
                'DockerrunTemplate' => file_get_contents('app/_AWS_/ELB-template/Dockerrun.aws.json.TEMPLATE'),
            ];
    }

    public function __construct()
    {

    }

    public function run($argv)
    {
        // === params ===
        $command = $argv[1] ?? null;
        $param1 = $argv[2] ?? null; // to use if needed
        $param2 = $argv[3] ?? null; // to use if needed

        // === validation ===
        if (!$command) {
            self::LineTag(TagEnum::ERROR)->print("missing command, should be '%s COMMAND'", OpsApp::APP_MAIN_COMMAND);
            $this->help();
            exit(); // END
        }
        if (!array_key_exists($command, CommandEnum::SUPPORT_COMMANDS())) {
            self::LineTag(TagEnum::ERROR)->print("do not support this command '%s'", $command);
            $this->help();
            exit(); // END
        }

        // === handle ===
        switch ($command) {
            // === this app ===
            case CommandEnum::HELP:
                $this->help();
                break;
            case CommandEnum::RELEASE:
                (new Release())->handle($argv);
                break;
            case CommandEnum::VERSION:
                self::LineNew()->print(OpsApp::version());
                break;
            case CommandEnum::SYNC:
                OPSHelper::sync();
                break;
            // === AWS related ===
            case CommandEnum::LOAD_ENV_OPS:
                echo AWSHelper::loadOpsEnvAndHandleMore();
                break;
            case CommandEnum::GET_SECRET_ENV:
                // validate
                if (!$param1) {
                    self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                        ->print("required secret name");
                    exit(); // END
                }
                // handle
                AWSHelper::getSecretEnv($param1, $param2);
                break;
            case CommandEnum::ELB_UPDATE_VERSION:
                AWSHelper::ELBUpdateVersion();
                break;
            // === Git / GitHub ===
            case CommandEnum::BRANCH:
                echo exec(GitHubEnum::GET_BRANCH_COMMAND);
                break;
            case CommandEnum::REPOSITORY:
                echo basename(str_replace('.git', '', exec(GitHubEnum::GET_REMOTE_ORIGIN_URL_COMMAND)));
                break;
            case CommandEnum::HEAD_COMMIT_ID:
                echo exec(GitHubEnum::GET_HEAD_COMMIT_ID_COMMAND);
                break;
            case CommandEnum::HANDLE_CACHES_AND_GIT:
                GitHubHelper::handleCachesAndGit($argv);
                break;
            case CommandEnum::FORCE_CHECKOUT:
                GitHubHelper::forceCheckout();
                break;
            //        GitHub Actions
            case CommandEnum::BUILD_ALL_PROJECTS:
                GitHubHelper::buildAllProject();
                break;
            // === Docker ===
            case CommandEnum::DOCKER_KEEP_IMAGE_BY:
                DockerHelper::keepImageBy($argv);
                break;
            case CommandEnum::DOCKER_FILE_ADD_ENVS:
                DockerHelper::DockerfileAddEnvs($argv);
                break;
            // === utils ===
            case CommandEnum::HOME_DIR:
                echo DirHelper::getHomeDir();
                break;
            case  CommandEnum::SCRIPT_DIR:
                echo DirHelper::getScriptDir();
                break;
            case CommandEnum::WORKING_DIR:
                echo DirHelper::getWorkingDir();
                break;
            case CommandEnum::REPLACE_TEXT_IN_FILE:
                StrHelper::replaceTextInFile($argv);
                break;
            case CommandEnum::SLACK:
                SlackService::sendMessage($argv);
                break;
            case CommandEnum::TMP:
                DirHelper::tmp($argv);
                break;
            case CommandEnum::POST_WORK:
                OPSHelper::postWork($argv);
                break;
            case CommandEnum::CLEAR_OPS_DIR:
                OPSHelper::clearOpsDir();
                break;
            // === private ===
            case CommandEnum::GET_S3_WHITE_LIST_IPS_DEVELOPMENT:
                echo OPSHelper::getS3WhiteListIpsDevelopment();
                break;
            case CommandEnum::UPDATE_GITHUB_TOKEN_ALL_PROJECT:
                OPSHelper::updateGitHubTokenAllProjects();
                break;
            // === validation ===
            case CommandEnum::VALIDATE:
                OPSHelper::validate($argv);
                break;
            // === UI/Text ===
            case CommandEnum::TITLE:
                // validate
                if (!$param1) {
                    self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                        ->print("required title text");
                    exit(); // END
                }
                // handle
                self::LineNew()->printTitle($param1);
                break;
            case CommandEnum::SUB_TITLE:
                // validate
                if (!$param1) {
                    self::LineTagMultiple([TagEnum::VALIDATION, TagEnum::ERROR, TagEnum::PARAMS])
                        ->print("required sub title text");
                    exit(); // END
                }
                // handle
                self::LineNew()->printSubTitle($param1);
                break;
            // === other ===
            default:
                echo "[ERROR] Unknown error";
                break;
        }
    }

    private function help()
    {
        self::LineNew()->print('')
            ->printTitle("%s v%s", self::APP_NAME, self::APP_VERSION)
            ->setTag(TagEnum::INFO)->print("usage:  %s COMMAND", OpsApp::APP_MAIN_COMMAND)
            ->setTag(TagEnum::NONE)->print("               %s COMMAND PARAM1 PARAM2 ...", OpsApp::APP_MAIN_COMMAND)
            ->setTag(TagEnum::NONE)->print('')
            ->setTag(TagEnum::INFO)->print("Support commands:");
        /**
         * @var  $command string
         * @var  $descriptionArr array
         */
        foreach (CommandEnum::SUPPORT_COMMANDS() as $command => $descriptionArr) {
            switch (count($descriptionArr)) {
                case 0: // group command's title
                    self::LineNew()->printSubTitle($command);
                    break;
                case 1: // group command's items - single line description
                    self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)->setIcon(IconEnum::HYPHEN)
                        ->print("%s     : %s", $command, $descriptionArr[0]);
                    break;
                default: // group command's items - multiple line description
                    self::LineIndent(IndentLevelEnum::SUB_ITEM_LINE)->setIcon(IconEnum::HYPHEN)->print($command);
                    foreach ($descriptionArr as $descriptionLine) {
                        self::LineIndent(IndentLevelEnum::LEVEL_3)->setIcon(IconEnum::DOT)->print($descriptionLine);
                    }
                    break;
            }
        }
        self::LineNew()->printSeparatorLine();
    }

    public static function version(Version $newVersion = null): string
    {
        return self::colorFormat(
            sprintf("%s v%s", self::APP_NAME, $newVersion ? $newVersion->toString() : self::APP_VERSION),
            UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD
        );
    }
}

// === end class zone ====

// === execute zone ===
(new OpsApp())->run($argv);
// === end execute zone ===

// === end Generated app class ===


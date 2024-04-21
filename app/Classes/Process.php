<?php

namespace App\Classes;

use App\Enum\AppInfoEnum;
use App\Enum\GitHubEnum;
use App\Enum\IconEnum;
use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Helpers\DirHelper;
use App\Helpers\StrHelper;
use App\Traits\ConsoleUITrait;

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
        //    handle alias when run alias 'myops' in Process
        $isContainsAlias = false;
        foreach ($this->commands as $command) {
            if (StrHelper::startWith($command, AppInfoEnum::APP_MAIN_COMMAND)) {
                $isContainsAlias = true;
                break;
            }
        }
        if ($isContainsAlias) {
            // replace alias
            for ($i = 0; $i < count($this->commands); $i++) {
                if (StrHelper::startWith($this->commands[$i], AppInfoEnum::APP_MAIN_COMMAND)) {
                    $this->commands[$i] = "php " . AppInfoEnum::RELEASE_PATH . substr($this->commands[$i], strlen(AppInfoEnum::APP_MAIN_COMMAND));
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

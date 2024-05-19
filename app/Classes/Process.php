<?php

namespace App\Classes;

use App\Classes\Base\CustomCollection;
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

    /** @var CustomCollection */
    private $commands;

    /** @var CustomCollection */
    private $output;

    /** @var int */
    private $outputIndentLevel = IndentLevelEnum::MAIN_LINE;

    /** @var bool */
    private $isExitOnError;

    /**
     * @param string|null $workName
     * @param string|null $workDir
     * @param CustomCollection|array|null $commands
     */
    public function __construct(
        string $workName = null,
        string $workDir = null,
               $commands = null
    )
    {
        $this->workName = $workName;
        $this->workDir = $workDir;
        $this->commands = $commands instanceof CustomCollection ? $commands : new CustomCollection($commands ?? []);
        // default
        $this->isExitOnError = true; // default
        $this->output = new CustomCollection();
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
     * @return CustomCollection|null
     */
    public function getCommands(): ?CustomCollection
    {
        return $this->commands;
    }

    /**
     * @param CustomCollection|null $commands
     * @return Process
     */
    public function setCommands(?CustomCollection $commands): Process
    {
        $this->commands = $commands;
        return $this;
    }

    /**
     * @return CustomCollection
     */
    public function getOutput(): CustomCollection
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getOutputStrAll(): string
    {
        return $this->output->join(PHP_EOL);
    }

    /**
     * @param CustomCollection $output
     * @return Process
     */
    public function setOutput(CustomCollection $output): Process
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return int
     */
    public function getOutputIndentLevel(): int
    {
        return $this->outputIndentLevel;
    }

    /**
     * @param int $outputIndentLevel
     * @return Process
     */
    public function setOutputIndentLevel(int $outputIndentLevel): Process
    {
        $this->outputIndentLevel = $outputIndentLevel;
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
                exitApp(ERROR_END);
            }
        }
        // === handle ===
        //    handle alias when run alias 'myops' in Process
        $isContainsAlias = false;
        foreach ($this->commands as $command) {
            if (StrHelper::startsWith($command, AppInfoEnum::APP_MAIN_COMMAND)) {
                $isContainsAlias = true;
                break;
            }
        }
        if ($isContainsAlias) {
            // replace alias
            for ($i = 0; $i < $this->commands->count(); $i++) {
                if (StrHelper::startsWith($this->commands->get($i), AppInfoEnum::APP_MAIN_COMMAND)) {
                    $this->commands->setStr($i, "php %s%s", AppInfoEnum::RELEASE_PATH, substr($this->commands->get($i), strlen(AppInfoEnum::APP_MAIN_COMMAND)));
                }
            }
        }
        //
        if ($this->commands->count()) {
            $resultCode = null;
            exec($this->commands->join(';'), $tempOutput, $exitCode);
            $this->output = new CustomCollection($tempOutput);
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
        $dirCommands = (new CustomCollection())->addStr("cd '%s'", $this->workDir); // cd
        if (!$skipCheckDir) {
            $dirCommands->add(GitHubEnum::GET_REPOSITORY_DIR_COMMAND); // check dir
        }
        $this->commands->merge($dirCommands, true);
        $this->execMulti();
        //
        return $this;
    }

    /**
     * will execMultiInWorkDir | skip check dir | return output string
     * @return string|null
     */
    public function execMultiInWorkDirAndGetOutputStrAll(): ?string
    {
        return $this->execMultiInWorkDir(true)->getOutputStrAll();
    }

    public function printOutput(): Process
    {
        self::LineIndent($this->getOutputIndentLevel())->printSeparatorLine()
            ->setTag(TagEnum::WORK)->print($this->workName);
        self::LineIndent($this->getOutputIndentLevel())->setIcon(IconEnum::PLUS)->print("Commands:");
        if ($this->commands) {
            foreach ($this->commands as $command) {
                self::LineIndent($this->getOutputIndentLevel() + IndentLevelEnum::ITEM_LINE)
                    ->setIcon(IconEnum::CHEVRON_RIGHT)->print(StrHelper::hideSensitiveInformation($command));
            }
        }
        self::LineIndent($this->getOutputIndentLevel())->setIcon(IconEnum::PLUS)->print("Output:");
        foreach ($this->output as $outputLine) {
            self::LineIndent($this->getOutputIndentLevel() + IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::DOT)->print(StrHelper::hideSensitiveInformation($outputLine));
        }
        //
        return $this;
    }


    // === END UTILS ZONE ===

}

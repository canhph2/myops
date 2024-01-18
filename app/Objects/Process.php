<?php

namespace app\Objects;

use app\Enum\GitHubEnum;
use app\Enum\IconEnum;
use app\Enum\IndentLevelEnum;
use app\Enum\TagEnum;
use app\Helpers\DIR;
use app\Helpers\GITHUB;
use app\Helpers\TEXT;

class Process
{
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
    private $isExistOnError;

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
        $this->isExistOnError = true; // default
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
    public function isExistOnError(): bool
    {
        return $this->isExistOnError;
    }

    /**
     * @param bool $isExistOnError
     * @return Process
     */
    public function setIsExistOnError(bool $isExistOnError): Process
    {
        $this->isExistOnError = $isExistOnError;
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
                sprintf("rm -rf %s", DIR::getHomeDir()),
                sprintf("rm -rf '%s'", DIR::getHomeDir()),
                sprintf("rm -rf \"%s\"", DIR::getHomeDir()),
                sprintf("rm -rf %s/", DIR::getHomeDir()),
                sprintf("rm -rf '%s/'", DIR::getHomeDir()),
                sprintf("rm -rf \"%s/\"", DIR::getHomeDir()),
            ])) {
                TEXT::tag(TagEnum::ERROR)->message("detect dangerous command: $command  , exit app");
                exit(1); // END
            }
        }
        // === handle ===
        if ($this->commands) {
            $resultCode = null;
            exec(join(';', $this->commands), $this->output, $exitCode);
            if ($exitCode && $this->isExistOnError) {
                $this->printOutput();
                TEXT::tag(TagEnum::ERROR)->message("detect execute shell command failed, exit app | exit code = $exitCode");
                exit($exitCode); // END app
            }
        }
        //
        return $this;
    }

    public function execMultiInWorkDir(bool $skipCheckDir = false): Process
    {
        // case not .git and want to use git commands
        if (!$skipCheckDir) {
            if (!GITHUB::isGit(DIR::getWorkingDir())) {
                TEXT::tag(TagEnum::ERROR)->message("detect no .git in this directory, init a fake repository");
                $arrDirCommands[] = sprintf("cd '%s'", DIR::getWorkingDir()); // cd to work dir
                $arrDirCommands[] = GitHubEnum::INIT_REPOSITORY_COMMAND;
            }
        }
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

    public function printOutput(): Process
    {
        TEXT::indent($this->getOutputParentIndentLevel())->setTag(TagEnum::WORK)->message($this->workName);
        TEXT::indent($this->getOutputParentIndentLevel())->setIcon(IconEnum::HYPHEN)->message("Commands:");
        if ($this->commands) {
            foreach ($this->commands as $command) {
                TEXT::indent($this->getOutputParentIndentLevel() + IndentLevelEnum::ITEM_LINE)
                    ->setIcon(IconEnum::CHEVRON_RIGHT)->message(TEXT::hideSensitiveInformation($command));
            }
        }
        TEXT::indent($this->getOutputParentIndentLevel())->setIcon(IconEnum::HYPHEN)->message("Output:");
        if ($this->output) {
            foreach ($this->output as $outputLine) {
                TEXT::indent($this->getOutputParentIndentLevel() + IndentLevelEnum::ITEM_LINE)->setIcon(IconEnum::PLUS)->message(TEXT::hideSensitiveInformation($outputLine));
            }
        }
        //
        return $this;
    }


    // === END UTILS ZONE ===

}

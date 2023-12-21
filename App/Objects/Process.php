<?php

namespace App\Objects;

use SharedLib\Enum\GitHubEnum;
use SharedLib\Helpers\DotENVHelper;
use SharedLib\Helpers\TextHelper;

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
     * @param array|null $output
     * @return Process
     */
    public function setOutput(?array $output): Process
    {
        $this->output = $output;
        return $this;
    }

    // === UTILS ZONE ===

    public function execMulti(): Process
    {
        // load .env
       $this ->commands = array_merge(DotENVHelper::loadDotENV_SHELLCommand(),$this ->commands);
        //
        if ($this->commands) {
            $resultCode = null;
            exec(join(';', $this->commands), $this->output, $resultCode);
        }
        //
        return $this;
    }

    public function execMultiInWorkDir(bool $skipCheckDir = false): Process
    {
        // dir commands
        $arrDirCommands[] = sprintf("cd '%s'", $this->workDir); // cd
        if (!$skipCheckDir) {
            $arrDirCommands[] = GitHubEnum::REPOSITORY_DIR_COMMAND; // check dir
        }
        $this->commands = array_merge($arrDirCommands, $this->commands);
        $this->execMulti();
        //
        return $this;
    }

    public function printOutput(): Process
    {
        TextHelper::message(sprintf("\n[WORK] %s", $this->workName));
        TextHelper::message("- Commands: ");
        if ($this->commands) {
            foreach ($this->commands as $command) {
                TextHelper::message(sprintf("    > %s", $command));
            }
        }
        TextHelper::message("- Output: ");
        if ($this->output) {
            foreach ($this->output as $outputLine) {
                TextHelper::message(sprintf("    + %s", $outputLine));
            }
        }
        //
        return $this;
    }

    // === END UTILS ZONE ===

}

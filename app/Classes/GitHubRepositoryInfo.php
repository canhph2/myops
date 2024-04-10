<?php

namespace app\Classes;

class GitHubRepositoryInfo
{
    /** @var string */
    private $repositoryName;

    /** @var string */
    private $username;

    /** @var bool */
    private $isGitHubAction;

    /** @var int */
    private $amountBuildTimeInMinute;

    /**
     * @param string $repositoryName
     * @param string $username
     * @param bool $isGitHubAction
     * @param int $amountBuildTimeInMinute
     */
    public function __construct(string $repositoryName, string $username, bool $isGitHubAction = false, int $amountBuildTimeInMinute = 0)
    {
        $this->repositoryName = $repositoryName;
        $this->username = $username;
        $this->isGitHubAction = $isGitHubAction;
        $this->amountBuildTimeInMinute = $amountBuildTimeInMinute;
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
     * @return int
     */
    public function getAmountBuildTimeInMinute(): int
    {
        return $this->amountBuildTimeInMinute;
    }

    /**
     * @return int
     */
    public function getAmountBuildTimeInSecond(): int
    {
        return $this->amountBuildTimeInMinute * 60;
    }

    /**
     * @param int $amountBuildTimeInMinute
     * @return GitHubRepositoryInfo
     */
    public function setAmountBuildTimeInMinute(int $amountBuildTimeInMinute): GitHubRepositoryInfo
    {
        $this->amountBuildTimeInMinute = $amountBuildTimeInMinute;
        return $this;
    }
}

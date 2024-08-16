<?php

namespace App\Classes;

class GitHubRepositoryInfo
{
    /** @var string */
    private $repositoryName;

    /** @var string */
    private $familyName;

    /** @var string */
    private $username;

    /** @var bool */
    private $isGitHubAction;

    /** @var string|null */
    private $sourceDir;

    /** @var string */
    private $currentBranch;

    /** @var string */
    private $currentWorkspaceDir;

    /**
     * @param string $repositoryName
     * @param string $familyName
     * @param string $username
     * @param bool $isGitHubAction
     * @param string|null $sourceDir
     */
    public function __construct(string $repositoryName, string $familyName, string $username,
                                bool   $isGitHubAction = false, string $sourceDir = null)
    {
        $this->repositoryName = $repositoryName;
        $this->familyName = $familyName;
        $this->username = $username;
        $this->isGitHubAction = $isGitHubAction;
        $this->sourceDir = $sourceDir;
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
    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    /**
     * @param string $familyName
     * @return GitHubRepositoryInfo
     */
    public function setFamilyName(string $familyName): GitHubRepositoryInfo
    {
        $this->familyName = $familyName;
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

    /**
     * @return string
     */
    public function getCurrentRepositorySourceDir(): string
    {
        return sprintf('%s/%s/%s', $this->currentWorkspaceDir, $this->repositoryName, $this->sourceDir);
    }

    public function getSourceDir(): ?string
    {
        return $this->sourceDir;
    }

    public function setSourceDir(?string $sourceDir): GitHubRepositoryInfo
    {
        $this->sourceDir = $sourceDir;
        return $this;
    }

}

<?php

namespace App\Objects;

use InvalidArgumentException;

/**
 * by BARD AI
 */
class Version
{
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

    public static function parse(string $versionStr): Version
    {
        $versionData = explode('.', trim($versionStr));
        return new Version($versionData[0], $versionData[1], $versionData[2]);
    }

    public function getMajor()
    {
        return $this->major;
    }

    public function getMinor()
    {
        return $this->minor;
    }

    public function getPatch()
    {
        return $this->patch;
    }

    public function getBuild()
    {
        return $this->build;
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

    public function bump($part = 'patch'): Version
    {
        if (!in_array($part, ['major', 'minor', 'patch', 'build'])) {
            throw new InvalidArgumentException('Invalid version part');
        }
        if ($part === 'major') {
            $this->major++;
        }
        if ($part === 'minor') {
            $this->minor++;
        }
        if ($part === 'patch') {
            $this->patch++;
        }
        if ($part === 'build') {
            $this->build++;
        }
        return $this;
    }
}

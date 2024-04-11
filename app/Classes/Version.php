<?php

namespace App\Classes;

use InvalidArgumentException;

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

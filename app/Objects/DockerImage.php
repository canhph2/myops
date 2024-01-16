<?php

namespace app\Objects;

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
    private $size;

    /**
     * @param string|null $repository
     * @param string|null $tag
     * @param string|null $id
     * @param string|null $createdAt
     * @param string|null $size
     */
    public function __construct(?string $repository, ?string $tag, ?string $id, ?string $createdAt, ?string $size)
    {
        $this->repository = $repository;
        $this->tag = $tag;
        $this->id = $id;
        $this->createdAt = $createdAt;
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

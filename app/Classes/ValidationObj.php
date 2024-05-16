<?php

namespace App\Classes;

class ValidationObj
{
    /** @var string|null */
    private $error;
    /** @var array */
    private $data;

    /**
     * @param string|null $error
     * @param array $data
     */
    public function __construct(string $error = null, array $data = [])
    {
        $this->error = $error;
        $this->data = $data;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(string $error = null): ValidationObj
    {
        $this->error = $error;
        return $this;
    }

    public function clearError(): ValidationObj
    {
        $this->error = null; // clear
        return $this;
    }


    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): ValidationObj
    {
        $this->data = $data;
        return $this;
    }

    // === functions ===
    public function fails(): bool
    {
        return (bool)$this->error;
    }
}

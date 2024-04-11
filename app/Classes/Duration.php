<?php

namespace app\Classes;

use DateInterval;

class Duration
{
    /** @var DateInterval */
    private $dateInterval;

    /** @var int */
    public $totalMinutes;

    /** @var int */
    public $totalSeconds;

    /**
     * @param DateInterval $dateInterval
     */
    public function __construct(DateInterval $dateInterval)
    {
        $this->dateInterval = $dateInterval;
        //
        $this->totalMinutes = (int)($this->dateInterval->days * 24 * 60 + $this->dateInterval->h * 60 + $this->dateInterval->i);
        $this->totalSeconds = (int)($this->dateInterval->days * 24 * 60 * 60 + $this->dateInterval->h * 60 * 60
            + $this->dateInterval->i * 60 + $this->dateInterval->s);
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return sprintf("%d minute%s %d second%s",
            $this->totalMinutes, $this->totalMinutes > 1 ? "s" : "",
            $this->dateInterval->s, $this->dateInterval->s > 1 ? "s" : ""
        );
    }
}

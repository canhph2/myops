<?php

namespace App\Helpers;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class DateHelper
{
    /**
     * @param DateTimeInterface $dateTime
     * @param DateTimeZone|null $timezone
     *
     * @return DateTime
     */
    public static function prepareDateTime(DateTimeInterface $dateTime, DateTimeZone $timezone = null): DateTime
    {
        return $dateTime instanceof DateTimeImmutable
            ? (new DateTime())->setTimestamp($dateTime->getTimestamp())
                ->setTimezone($timezone ?? new DateTimeZone('Z')) // case DateTimeImmutable
            : $dateTime->setTimezone($timezone ?? new DateTimeZone('Z')); // case DateTime
    }

    /**
     * @param DateTimeInterface|null $dateTime1
     * @param DateTimeInterface|null $dateTime2
     *
     * @return bool
     */
    public static function isSameDatetime(?DateTimeInterface $dateTime1, ?DateTimeInterface $dateTime2): bool
    {
        $dateTime1Format = $dateTime1 ? $dateTime1->format('Y-m-d H:i:s') : '';
        $dateTime2Format = $dateTime2 ? $dateTime2->format('Y-m-d H:i:s') : '';

        return $dateTime1Format === $dateTime2Format;
    }

    public static function totalSeconds(DateTimeInterface $dateTime1, DateTimeInterface $dateTime2): int
    {
        $interval = $dateTime1->diff($dateTime2);
        return $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->d * 86400);
    }

    public static function getTimePeriodText(DateTimeInterface $dateTime1, DateTimeInterface $dateTime2): string
    {
        $interval = $dateTime1->diff($dateTime2);
        $days = $interval->days ? sprintf("%s day%s", $interval->days, $interval->days > 1 ? 's' : '') : '';
        $hours = $interval->h ? sprintf(" %s hour%s", $interval->h, $interval->h > 1 ? 's' : '') : '';
        $minutes = $interval->i ? sprintf(" %s minute%s", $interval->i, $interval->i > 1 ? 's' : '') : '';
        $seconds = $interval->s ? sprintf(" %s second%s", $interval->s, $interval->s > 1 ? 's' : '') : '';
        return trim("$days$hours$minutes$seconds") ?: '0 second';
    }
}

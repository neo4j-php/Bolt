<?php

namespace Bolt\structures;

/**
 * Class DateTime
 * Immutable
 *
 * An instant capturing the date, the time, and the time zone.
 * The time zone information is specified with a zone offset.
 *
 * To convert to UTC:
 * <pre> utc_nanoseconds = (seconds * 1000000000) + nanoseconds - (tx_offset_minutes * 60 * 1000000000) </pre>
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class DateTime
{

    /**
     * @var int
     */
    private $seconds;

    /**
     * @var int
     */
    private $nanoseconds;

    /**
     * @var int
     */
    private $tz_offset_seconds;

    /**
     * DateTime constructor.
     * @param int $seconds
     * @param int $nanoseconds
     * @param int $tz_offset_seconds
     */
    public function __construct(int $seconds, int $nanoseconds, int $tz_offset_seconds)
    {
        $this->seconds = $seconds;
        $this->nanoseconds = $nanoseconds;
        $this->tz_offset_seconds = $tz_offset_seconds;
    }

    /**
     * seconds since the adjusted Unix epoch. This is not UTC
     * @return int
     */
    public function seconds(): int
    {
        return $this->seconds;
    }

    /**
     * @return int
     */
    public function nanoseconds(): int
    {
        return $this->nanoseconds;
    }

    /**
     * specifies the offset in minutes from UTC
     * @return int
     */
    public function tz_offset_seconds(): int
    {
        return $this->tz_offset_seconds;
    }

}

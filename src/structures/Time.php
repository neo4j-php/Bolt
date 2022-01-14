<?php

namespace Bolt\structures;

/**
 * Class Time
 * Immutable
 *
 * An instant capturing the time of day, and the timezone, but not the date
 *
 * To convert to UTC use:
 * <pre> utc_nanoseconds = nanoseconds - (tz_offset_seconds * 1000000000) </pre>
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#time---structure
 * @package Bolt\structures
 */
class Time implements IStructure
{
    /**
     * @var int
     */
    private $nanoseconds;

    /**
     * @var int
     */
    private $tz_offset_seconds;

    /**
     * Time constructor.
     * @param int $nanoseconds
     * @param int $tz_offset_seconds
     */
    public function __construct(int $nanoseconds, int $tz_offset_seconds)
    {
        $this->nanoseconds = $nanoseconds;
        $this->tz_offset_seconds = $tz_offset_seconds;
    }

    /**
     * nanoseconds since midnight. This time is not UTC
     * @return int
     */
    public function nanoseconds(): int
    {
        return $this->nanoseconds;
    }

    /**
     * offset in seconds from UTC
     * @return int
     */
    public function tz_offset_seconds(): int
    {
        return $this->tz_offset_seconds;
    }

    public function __toString(): string
    {
        return \DateTime::createFromFormat('U.u', bcsub(bcdiv($this->nanoseconds, 1000000000, 6), $this->tz_offset_seconds, 6), new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone(sprintf('+%04d', $this->tz_offset_seconds / 3600 * 100)))
            ->format('H:i:s.uP');
    }
}

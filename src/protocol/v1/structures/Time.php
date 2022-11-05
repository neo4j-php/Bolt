<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

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
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-time
 * @package Bolt\protocol\v1\structures
 */
class Time implements IStructure
{
    private int $nanoseconds;
    private int $tz_offset_seconds;

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
        $value = sprintf("%09d", $this->nanoseconds - $this->tz_offset_seconds * 1e9);
        $seconds = substr($value, 0, -9);
        if (empty($seconds))
            $seconds = '0';
        $fraction = substr($value, -9, 6);

        return \DateTime::createFromFormat('U.u', $seconds . '.' . $fraction, new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone(sprintf("%+'05d", $this->tz_offset_seconds / 3600 * 100)))
            ->format('H:i:s.uP');
    }
}

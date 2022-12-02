<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

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
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-legacy-datetime
 * @package Bolt\protocol\v1\structures
 */
class DateTime implements IStructure
{
    public function __construct(
        private int $seconds,
        private int $nanoseconds,
        private int $tz_offset_seconds
    )
    {
    }

    /**
     * seconds since the adjusted Unix epoch. This is not UTC
     */
    public function seconds(): int
    {
        return $this->seconds;
    }

    public function nanoseconds(): int
    {
        return $this->nanoseconds;
    }

    /**
     * specifies the offset in seconds from UTC
     */
    public function tz_offset_seconds(): int
    {
        return $this->tz_offset_seconds;
    }

    public function __toString(): string
    {
        $datetime = sprintf("%d", $this->seconds - $this->tz_offset_seconds)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime, new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone(sprintf("%+'05d", $this->tz_offset_seconds / 3600 * 100)))
            ->format('Y-m-d\TH:i:s.uP');
    }
}

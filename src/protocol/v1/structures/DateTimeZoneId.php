<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class DateTimeZoneId
 * Immutable
 *
 * An instant capturing the date, the time, and the time zone.
 * The time zone information is specified with a zone identification number.
 *
 * To convert to UTC:
 * <pre> utc_nanoseconds = (seconds * 1000000000) + nanoseconds - get_offset_in_nanoseconds(tz_id) </pre>
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-legacy-datetimezoneid
 * @package Bolt\protocol\v1\structures
 */
class DateTimeZoneId implements IStructure
{
    private int $seconds;
    private int $nanoseconds;
    private string $tz_id;

    /**
     * DateTimeZoneId constructor.
     * @param int $seconds
     * @param int $nanoseconds
     * @param string $tz_id
     */
    public function __construct(int $seconds, int $nanoseconds, string $tz_id)
    {
        $this->seconds = $seconds;
        $this->nanoseconds = $nanoseconds;
        $this->tz_id = $tz_id;
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
     * identifier for a specific time zone
     * @return string
     */
    public function tz_id(): string
    {
        return $this->tz_id;
    }

    public function __toString(): string
    {
        $datetime = sprintf("%d", $this->seconds)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime, new \DateTimeZone($this->tz_id))
                ->format('Y-m-d\TH:i:s.u') . '[' . $this->tz_id . ']';
    }
}

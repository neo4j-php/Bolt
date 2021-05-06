<?php

namespace Bolt\structures;

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
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class DateTimeZoneId implements IStructure
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
     * @var string
     */
    private $tz_id;

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
        $dt = \DateTime::createFromFormat('U', $this->seconds, new \DateTimeZone('UTC'));
        $fraction = new \DateInterval('PT0S');
        $fraction->f = $this->nanoseconds / 1000000000;
        $dt->add($fraction);
        $dt->setTimezone(new \DateTimeZone($this->tz_id));
        return $dt->format('Y-m-d\TH:i:s.uP');
    }
}

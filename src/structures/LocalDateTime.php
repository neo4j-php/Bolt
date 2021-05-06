<?php

namespace Bolt\structures;

/**
 * Class LocalDateTime
 * Immutable
 *
 * An instant capturing the date and the time, but not the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class LocalDateTime implements IStructure
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
     * LocalDateTime constructor.
     * @param int $seconds
     * @param int $nanoseconds
     */
    public function __construct(int $seconds, int $nanoseconds)
    {
        $this->seconds = $seconds;
        $this->nanoseconds = $nanoseconds;
    }

    /**
     * seconds since the Unix epoch
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

    public function __toString(): string
    {
        $dt = \DateTime::createFromFormat('U', $this->seconds, new \DateTimeZone('UTC'));
        $fraction = new \DateInterval('PT0S');
        $fraction->f = $this->nanoseconds / 1000000000;
        $dt->add($fraction);
        return $dt->format('Y-m-d\TH:i:s.u');
    }

}

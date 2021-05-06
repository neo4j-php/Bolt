<?php

namespace Bolt\structures;

/**
 * Class LocalTime
 * Immutable
 *
 * An instant capturing the time of day, but not the date, nor the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class LocalTime implements IStructure
{
    /**
     * @var int
     */
    private $nanoseconds;

    /**
     * LocalTime constructor.
     * @param int $nanoseconds
     */
    public function __construct(int $nanoseconds)
    {
        $this->nanoseconds = $nanoseconds;
    }

    /**
     * nanosecond since midnight
     * @return int
     */
    public function nanoseconds(): int
    {
        return $this->nanoseconds;
    }

    public function __toString(): string
    {
        return date('H:i:s', floor($this->nanoseconds / 1000000000)) . '.' . $this->nanoseconds % 1000000000;
    }
}

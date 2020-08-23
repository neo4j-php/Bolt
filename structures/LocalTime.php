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
class LocalTime
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

}

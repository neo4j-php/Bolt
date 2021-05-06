<?php

namespace Bolt\structures;

/**
 * Class Duration
 * Immutable
 *
 * A temporal amount. This captures the difference in time between two instants. It only captures the amount of time between two instants, it does not capture a start time and end time. A unit capturing the start time and end time would be a Time Interval and is out of scope for this proposal.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class Duration implements IStructure
{

    /**
     * @var int
     */
    private $months;

    /**
     * @var int
     */
    private $days;

    /**
     * @var int
     */
    private $seconds;

    /**
     * @var int
     */
    private $nanoseconds;

    /**
     * Duration constructor.
     * @param int $months
     * @param int $days
     * @param int $seconds
     * @param int $nanoseconds
     */
    public function __construct(int $months, int $days, int $seconds, int $nanoseconds)
    {
        $this->months = $months;
        $this->days = $days;
        $this->seconds = $seconds;
        $this->nanoseconds = $nanoseconds;
    }

    /**
     * @return int
     */
    public function months(): int
    {
        return $this->months;
    }

    /**
     * @return int
     */
    public function days(): int
    {
        return $this->days;
    }

    /**
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
        return 'P' . $this->months . 'M' . $this->days . 'DT' . ($this->seconds + $this->nanoseconds / 1000000000) . 'S';
    }
}

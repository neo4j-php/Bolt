<?php

namespace Bolt\structures;

/**
 * Class Duration
 * Immutable
 *
 * A temporal amount. This captures the difference in time between two instants. It only captures the amount of time between two instants, it does not capture a start time and end time. A unit capturing the start time and end time would be a Time Interval and is out of scope for this proposal.
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#duration---structure
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
        $output = 'P';
        $years = floor($this->months / 12);
        if (!empty($years))
            $output .= $years . 'Y';
        if (!empty($this->months % 12))
            $output .= ($this->months % 12) . 'M';
        if (!empty($this->days))
            $output .= $this->days . 'D';

        $time = '';
        $hours = floor($this->seconds / 3600);
        if (!empty($hours))
            $time .= $hours . 'H';
        $minutes = floor($this->seconds % 3600 / 60);
        if (!empty($minutes))
            $time .= $minutes . 'M';

        $seconds = rtrim(sprintf("%d", $this->seconds % 3600 % 60)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6), '0.');
        if (!empty($seconds))
            $time .= $seconds . 'S';

        if (!empty($time))
            $output .= 'T' . $time;

        return $output;
    }
}

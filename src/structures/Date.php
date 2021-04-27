<?php

namespace Bolt\structures;

/**
 * Class Date
 * Immutable
 *
 * An instant capturing the date, but not the time, nor the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class Date implements IStructure
{
    /**
     * @var int
     */
    private $days;

    /**
     * Date constructor.
     * @param int $days
     */
    public function __construct(int $days)
    {
        $this->days = $days;
    }

    /**
     * days since the Unix epoch
     * @return int
     */
    public function days(): int
    {
        return $this->days;
    }

    public function __toString(): string
    {
        return date('Y-m-d', strtotime($this->days . ' days'));
    }
}

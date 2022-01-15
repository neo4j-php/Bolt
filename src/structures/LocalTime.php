<?php

namespace Bolt\structures;

/**
 * Class LocalTime
 * Immutable
 *
 * An instant capturing the time of day, but not the date, nor the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#localtime---structure
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
        return \DateTime::createFromFormat('U.u', bcdiv($this->nanoseconds, 10e8, 6))
            ->format('H:i:s.u');
    }
}

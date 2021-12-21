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
        $value = sprintf("%09d", $this->nanoseconds);
        $seconds = substr($value, 0, -9);
        if (empty($seconds))
            $seconds = '0';
        $fraction = substr($value, -9, 6);

        return \DateTime::createFromFormat('U.u', $seconds . '.' . $fraction)
            ->format('H:i:s.u');
    }
}

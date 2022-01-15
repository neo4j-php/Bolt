<?php

namespace Bolt\structures;

/**
 * Class LocalDateTime
 * Immutable
 *
 * An instant capturing the date and the time, but not the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#localdatetime---structure
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
        $datetime = sprintf("%d", $this->seconds)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime)
            ->format('Y-m-d\TH:i:s.u');
    }

}

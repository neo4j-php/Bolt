<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class LocalDateTime
 * Immutable
 *
 * An instant capturing the date and the time, but not the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-localdatetime
 * @package Bolt\protocol\v1\structures
 */
class LocalDateTime implements IStructure
{
    private int $seconds;
    private int $nanoseconds;

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

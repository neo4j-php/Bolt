<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Date
 * Immutable
 *
 * An instant capturing the date, but not the time, nor the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-date
 * @package Bolt\protocol\v1\structures
 */
class Date implements IStructure
{
    private int $days;

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
        return gmdate('Y-m-d', strtotime(sprintf("%+d", $this->days) . ' days +0000', 0));
    }
}

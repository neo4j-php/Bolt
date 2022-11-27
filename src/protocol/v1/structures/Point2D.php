<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Point2D
 * Immutable
 *
 * Represents a single location in 2-dimensional space.
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-point2d
 * @package Bolt\protocol\v1\structures
 */
class Point2D implements IStructure
{
    public function __construct(
        private int   $srid,
        private float $x,
        private float $y
    )
    {
    }

    /**
     * Spatial Reference System Identifier
     */
    public function srid(): int
    {
        return $this->srid;
    }

    public function x(): float
    {
        return $this->x;
    }

    public function y(): float
    {
        return $this->y;
    }

    public function __toString(): string
    {
        return 'point({srid: ' . $this->srid . ', ' . 'x: ' . $this->x . ', y: ' . $this->y . '})';
    }
}

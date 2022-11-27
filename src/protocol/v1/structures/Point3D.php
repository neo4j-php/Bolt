<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Point3D
 * Immutable
 *
 * Represents a single location in space.
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-point3d
 * @package Bolt\protocol\v1\structures
 */
class Point3D implements IStructure
{
    public function __construct(
        private int   $srid,
        private float $x,
        private float $y,
        private float $z
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

    public function z(): float
    {
        return $this->z;
    }

    public function __toString(): string
    {
        return 'point({srid: ' . $this->srid . ', ' . 'x: ' . $this->x . ', y: ' . $this->y . ', z: ' . $this->z . '})';
    }

}

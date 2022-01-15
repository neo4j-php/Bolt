<?php

namespace Bolt\structures;

/**
 * Class Point3D
 * Immutable
 *
 * Represents a single location in space.
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#point3d---structure
 * @package Bolt\structures
 */
class Point3D implements IStructure
{
    /**
     * @var int
     */
    private $srid;

    /**
     * @var float
     */
    private $x;

    /**
     * @var float
     */
    private $y;

    /**
     * @var float
     */
    private $z;

    /**
     * Point3D constructor.
     * @param int $srid
     * @param float $x
     * @param float $y
     * @param float $z
     */
    public function __construct(int $srid, float $x, float $y, float $z)
    {
        $this->srid = $srid;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * Spatial Reference System Identifier
     * @return int
     */
    public function srid(): int
    {
        return $this->srid;
    }

    /**
     * @return float
     */
    public function x(): float
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function y(): float
    {
        return $this->y;
    }

    /**
     * @return float
     */
    public function z(): float
    {
        return $this->z;
    }

    public function __toString(): string
    {
        return 'point({srid: ' . $this->srid . ', ' . 'x: ' . $this->x . ', y: ' . $this->y . ', z: ' . $this->z . '})';
    }

}

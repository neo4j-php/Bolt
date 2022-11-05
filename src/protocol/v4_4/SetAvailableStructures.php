<?php

namespace Bolt\protocol\v4_4;

use Bolt\structures\{
    Date,
    DateTime,
    DateTimeZoneId,
    Duration,
    LocalDateTime,
    LocalTime,
    Node,
    Path,
    Point2D,
    Point3D,
    Relationship,
    Time,
    UnboundRelationship
};

/**
 * Trait to set available structures
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/
 * @package Bolt\protocol
 */
trait SetAvailableStructures
{
    /**
     * Set available structures
     */
    protected function setAvailableStructures()
    {
        $this->packer->setAvailableStructures([
            0x44 => Date::class,
            0x54 => Time::class,
            0x74 => LocalTime::class,
            0x46 => DateTime::class,
            0x49 => DateTime::class, //todo new class
            0x66 => DateTimeZoneId::class,
            0x69 => DateTimeZoneId::class, //todo new class
            0x64 => LocalDateTime::class,
            0x45 => Duration::class,
            0x58 => Point2D::class,
            0x59 => Point3D::class,
        ]);

        $this->unpacker->setAvailableStructures([
            0x4E => Node::class,
            0x52 => Relationship::class,
            0x72 => UnboundRelationship::class,
            0x50 => Path::class,
            0x44 => Date::class,
            0x54 => Time::class,
            0x74 => LocalTime::class,
            0x46 => DateTime::class,
            0x49 => DateTime::class, //todo new class
            0x66 => DateTimeZoneId::class,
            0x69 => DateTimeZoneId::class, //todo new class
            0x64 => LocalDateTime::class,
            0x45 => Duration::class,
            0x58 => Point2D::class,
            0x59 => Point3D::class,
        ]);
    }
}

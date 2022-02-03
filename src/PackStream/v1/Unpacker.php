<?php

namespace Bolt\PackStream\v1;

use Bolt\structures\{
    IStructure,
    Node,
    Relationship,
    UnboundRelationship,
    Path,
    Date,
    Time,
    LocalTime,
    DateTime,
    DateTimeZoneId,
    LocalDateTime,
    Duration,
    Point2D,
    Point3D
};
use Bolt\PackStream\IUnpacker;
use Bolt\error\UnpackException;

/**
 * Class Unpacker of PackStream version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\PackStream\v1
 */
class Unpacker implements IUnpacker
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var bool
     */
    private $littleEndian;

    /**
     * @var int
     */
    private $signature;

    private $structuresLt = [
        0x4E => [Node::class, 'unpackInteger', 'unpackList', 'unpackMap'],
        0x52 => [Relationship::class, 'unpackInteger', 'unpackInteger', 'unpackInteger', 'unpackString', 'unpackMap'],
        0x72 => [UnboundRelationship::class, 'unpackInteger', 'unpackString', 'unpackMap'],
        0x50 => [Path::class, 'unpackList', 'unpackList', 'unpackList'],
        0x44 => [Date::class, 'unpackInteger'],
        0x54 => [Time::class, 'unpackInteger', 'unpackInteger'],
        0x74 => [LocalTime::class, 'unpackInteger'],
        0x46 => [DateTime::class, 'unpackInteger', 'unpackInteger', 'unpackInteger'],
        0x66 => [DateTimeZoneId::class, 'unpackInteger', 'unpackInteger', 'unpackString'],
        0x64 => [LocalDateTime::class, 'unpackInteger', 'unpackInteger'],
        0x45 => [Duration::class, 'unpackInteger', 'unpackInteger', 'unpackInteger', 'unpackInteger'],
        0x58 => [Point2D::class, 'unpackInteger', 'unpackFloat', 'unpackFloat'],
        0x59 => [Point3D::class, 'unpackInteger', 'unpackFloat', 'unpackFloat', 'unpackFloat']
    ];

    /**
     * @inheritDoc
     * @throws UnpackException
     */
    public function unpack(string $msg)
    {
        if (empty($msg)) {
            return null;
        }

        $this->littleEndian = unpack('S', "\x01\x00")[1] === 1;
        $this->offset = 0;
        $this->message = $msg;

        return $this->u();
    }

    /**
     * @inheritDoc
     */
    public function getSignature(): int
    {
        return $this->signature;
    }

    /**
     * Get next bytes from message
     * @param int $length
     * @return string
     */
    private function next(int $length): string
    {
        $str = mb_strcut($this->message, $this->offset, $length, '8bit');
        $this->offset += mb_strlen($str, '8bit');
        return $str;
    }

    /**
     * @return mixed
     * @throws UnpackException
     */
    private function u()
    {
        $marker = ord($this->next(1));

        if ($marker == 0xC3) {
            return true;
        }
        if ($marker == 0xC2) {
            return false;
        }
        if ($marker == 0xC0) {
            return null;
        }

        $output = $this->unpackInteger($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackFloat($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackString($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackList($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackMap($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackStruct($marker);
        if ($output !== null) {
            return $output;
        }

        return null;
    }

    /**
     * @param int $marker
     * @return array|IStructure|null
     * @throws UnpackException
     */
    private function unpackStruct(int $marker)
    {
        if ($marker >> 4 == 0b1011) { //TINY_STRUCT
            $size = 0b10110000 ^ $marker;
        } elseif ($marker == 0xDC) { //STRUCT_8
            $size = unpack('C', $this->next(1));
        } elseif ($marker == 0xDD) { //STRUCT_16
            $size = unpack('n', $this->next(2));
        } else {
            return null;
        }

        $signature = ord($this->next(1));

        if (array_key_exists($signature, $this->structuresLt)) {
            if ($size + 1 !== count($this->structuresLt[$signature]))
                throw new UnpackException('Incorrect amount of structure fields for ' . reset($this->structuresLt[$signature]));
            return $this->unpackSpecificStructure(...$this->structuresLt[$signature]);
        } else {
            $this->signature = $signature;
            return $this->u();
        }
    }

    /**
     * Dynamic predefined specific structure unpacking
     * @param string $class
     * @param string ...$methods
     * @return IStructure
     * @throws UnpackException
     */
    private function unpackSpecificStructure(string $class, string ...$methods): IStructure
    {
        $values = [];
        foreach ($methods as $method) {
            $marker = ord($this->next(1));
            $value = $this->{$method}($marker);
            if ($value === null)
                throw new UnpackException('Structure call for method "' . $method . '" generated unpack error');
            $values[] = $value;
        }

        return new $class(...$values);
    }

    /**
     * @param int $marker
     * @return array
     * @throws UnpackException
     */
    private function unpackMap(int $marker): ?array
    {
        if ($marker >> 4 == 0b1010) { //TINY_MAP
            $size = 0b10100000 ^ $marker;
        } elseif ($marker == 0xD8) { //MAP_8
            $size = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xD9) { //MAP_16
            $size = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xDA) { //MAP_32
            $size = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        $output = [];
        for ($i = 0; $i < $size; $i++) {
            $output[$this->u()] = $this->u();
        }
        return $output;
    }

    /**
     * @param int $marker
     * @return string
     */
    private function unpackString(int $marker): ?string
    {
        if ($marker >> 4 == 0b1000) { //TINY_STRING
            $length = 0b10000000 ^ $marker;
        } elseif ($marker == 0xD0) { //STRING_8
            $length = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xD1) { //STRING_16
            $length = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xD2) { //STRING_32
            $length = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        return $this->next($length);
    }

    /**
     * @param int $marker
     * @return int
     */
    private function unpackInteger(int $marker): ?int
    {
        if ($marker >> 7 == 0b0) { //+TINY_INT
            return $marker;
        } elseif ($marker >> 4 == 0b1111) { //-TINY_INT
            return (int)unpack('c', strrev(chr($marker)))[1];
        } elseif ($marker == 0xC8) { //INT_8
            return (int)unpack('c', $this->next(1))[1];
        } elseif ($marker == 0xC9) { //INT_16
            $value = $this->next(2);
            return (int)unpack('s', $this->littleEndian ? strrev($value) : $value)[1];
        } elseif ($marker == 0xCA) { //INT_32
            $value = $this->next(4);
            return (int)unpack('l', $this->littleEndian ? strrev($value) : $value)[1];
        } elseif ($marker == 0xCB) { //INT_64
            $value = $this->next(8);
            return (int)unpack("q", $this->littleEndian ? strrev($value) : $value)[1];
        } else {
            return null;
        }
    }

    /**
     * @param int $marker
     * @return float
     */
    private function unpackFloat(int $marker): ?float
    {
        if ($marker == 0xC1) {
            return (float)unpack('d', strrev($this->next(8)))[1];
        } else {
            return null;
        }
    }

    /**
     * @param int $marker
     * @return array
     * @throws UnpackException
     */
    private function unpackList(int $marker): ?array
    {
        if ($marker >> 4 == 0b1001) { //TINY_LIST
            $size = 0b10010000 ^ $marker;
        } elseif ($marker == 0xD4) { //LIST_8
            $size = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xD5) { //LIST_16
            $size = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xD6) { //LIST_32
            $size = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        $output = [];
        for ($i = 0; $i < $size; $i++) {
            $output[] = $this->u();
        }

        return $output;
    }

}

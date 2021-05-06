<?php

namespace Bolt\PackStream\v1;

use Bolt\structures\{
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
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\PackStream\v1
 */
class Unpacker implements IUnpacker
{
    /**
     * @var string
     */
    private $message;

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
     * Unpack message
     * @param string $msg
     * @param int &$signature
     * @return mixed
     * @throws UnpackException
     */
    public function unpack(string $msg, int &$signature)
    {
        if (empty($msg)) {
            return null;
        }

        $this->message = $msg;

        $size = 0;
        $marker = ord($this->next(1));
        if ($marker == 0xDC) { //STRUCT_8
            $size = unpack('C', $this->next(1));
        } elseif ($marker == 0xDD) { //STRUCT_16
            $size = unpack('n', $this->next(2));
        } elseif ($marker >> 4 == 0b1011) { //TINY_STRUCT
            $size = 0b10110000 ^ $marker;
        }

        $signature = ord($this->next(1));
        return $this->u();
    }

    /**
     * Get next bytes from message
     * @param int $length
     * @return string
     */
    private function next(int $length): string
    {
        $output = mb_strcut($this->message, 0, $length, '8bit');
        $this->message = mb_strcut($this->message, $length, null, '8bit');
        return $output;
    }

    /**
     * @return mixed
     * @throws UnpackException
     */
    private function u()
    {
        $marker = ord($this->next(1));
        $result = false;

        $output = $this->unpackStruct($marker, $result);
        if ($result) {
            return $output;
        }

        if ($marker == 0xC3) {
            return true;
        }
        if ($marker == 0xC2) {
            return false;
        }
        if ($marker == 0xC0) {
            return null;
        }

        $output = $this->unpackFloat($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackString($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackList($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackMap($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackInteger($marker, $result);
        if ($result) {
            return $output;
        }

        return null;
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return mixed|null
     * @throws UnpackException
     */
    private function unpackStruct(int $marker, bool &$result = false)
    {
        $size = 0;
        if ($marker == 0xDC) { //STRUCT_8
            $size = unpack('C', $this->next(1));
            $result = true;
        } elseif ($marker == 0xDD) { //STRUCT_16
            $size = unpack('n', $this->next(2));
            $result = true;
        } elseif ($marker >> 4 == 0b1011) { //TINY_STRUCT
            $size = 0b10110000 ^ $marker;
            $result = true;
        }

        if (!$result) {
            return null;
        }

        $marker = ord($this->next(1));
        $result = false;

        if (array_key_exists($marker, $this->structuresLt)) {
            $output = $this->unpackSpecificStructure($result, ...$this->structuresLt[$marker]);
            if ($result)
                return $output;
        }

        return null;
    }

    /**
     * Dynamic predefined specific structure unpacking
     * @param bool $result
     * @param string $class
     * @param mixed ...$methods
     * @return mixed
     * @throws UnpackException
     */
    private function unpackSpecificStructure(bool &$result, string $class, ...$methods)
    {
        $output = [];
        foreach ($methods as $method) {
            $result = false;
            $marker = ord($this->next(1));
            $output[] = $this->{$method}($marker, $result);
            if (!$result)
                throw new UnpackException('Structure call for method "' . $method . '" generated unpack error');
        }

        return new $class(...$output);
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return array
     * @throws UnpackException
     */
    private function unpackMap(int $marker, bool &$result = false): array
    {
        $size = -1;
        if ($marker >> 4 == 0b1010) { //TINY_MAP
            $size = 0b10100000 ^ $marker;
        } elseif ($marker == 0xD8) { //MAP_8
            $size = unpack('C', $this->next(1))[1] ?? $size;
        } elseif ($marker == 0xD9) { //MAP_16
            $size = unpack('n', $this->next(2))[1] ?? $size;
        } elseif ($marker == 0xDA) { //MAP_32
            $size = unpack('N', $this->next(4))[1] ?? $size;
        }

        $output = [];
        if ($size != -1) {
            for ($i = 0; $i < $size; $i++) {
                $output[$this->u()] = $this->u();
            }
            $result = true;
        }

        return $output;
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return string
     */
    private function unpackString(int $marker, bool &$result = false): string
    {
        $length = -1;
        if ($marker >> 4 == 0b1000) { //TINY_STRING
            $length = 0b10000000 ^ $marker;
        } elseif ($marker == 0xD0) { //STRING_8
            $length = unpack('C', $this->next(1))[1] ?? $length;
        } elseif ($marker == 0xD1) { //STRING_16
            $length = unpack('n', $this->next(2))[1] ?? $length;
        } elseif ($marker == 0xD2) { //STRING_32
            $length = unpack('N', $this->next(4))[1] ?? $length;
        }

        $output = '';
        if ($length != -1) {
            $output = $this->next($length);
            $result = true;
        }

        return $output;
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return int
     */
    private function unpackInteger(int $marker, bool &$result = false): int
    {
        $output = null;
        $tmp = unpack('S', "\x01\x00");
        $little = $tmp[1] == 1;

        if ($marker >> 7 == 0b0) { //+TINY_INT
            $output = $marker;
        } elseif ($marker >> 4 == 0b1111) { //-TINY_INT
            $output = unpack('c', strrev(chr($marker)))[1] ?? 0;
        } elseif ($marker == 0xC8) { //INT_8
            $output = unpack('c', $this->next(1))[1] ?? 0;
        } elseif ($marker == 0xC9) { //INT_16
            $value = $this->next(2);
            $output = unpack('s', $little ? strrev($value) : $value)[1] ?? 0;
        } elseif ($marker == 0xCA) { //INT_32
            $value = $this->next(4);
            $output = unpack('l', $little ? strrev($value) : $value)[1] ?? 0;
        } elseif ($marker == 0xCB) { //INT_64
            $value = $this->next(8);
            $output = unpack("q", $little ? strrev($value) : $value)[1] ?? 0;
        }

        if ($output !== null) {
            $result = true;
        }
        return (int) $output;
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return float
     */
    private function unpackFloat(int $marker, bool &$result = false): float
    {
        $output = 0;
        if ($marker == 0xC1) {
            $output = unpack('d', strrev($this->next(8)))[1] ?? 0;
            $result = true;
        }
        return $output;
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return array
     * @throws UnpackException
     */
    private function unpackList(int $marker, bool &$result = false): array
    {
        $size = -1;
        if ($marker >> 4 == 0b1001) { //TINY_LIST
            $size = 0b10010000 ^ $marker;
        } elseif ($marker == 0xD4) { //LIST_8
            $size = unpack('C', $this->next(1))[1] ?? $size;
        } elseif ($marker == 0xD5) { //LIST_16
            $size = unpack('n', $this->next(2))[1] ?? $size;
        } elseif ($marker == 0xD6) { //LIST_32
            $size = unpack('N', $this->next(4))[1] ?? $size;
        }

        $output = [];
        if ($size != -1) {
            for ($i = 0; $i < $size; $i++) {
                $output[] = $this->u();
            }
            $result = true;
        }

        return $output;
    }

}

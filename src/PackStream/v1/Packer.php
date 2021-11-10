<?php

namespace Bolt\PackStream\v1;

use Bolt\PackStream\IPacker;
use Bolt\error\PackException;
use Generator;

/**
 * Class Packer of PackStream version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\PackStream\v1
 */
class Packer implements IPacker
{
    private const SMALL = 16;
    private const MEDIUM = 256;
    private const LARGE = 65536;
    private const HUGE = 4294967295;

    /**
     * Pack message with parameters
     * @param $signature
     * @param mixed ...$params
     * @return Generator
     * @throws PackException
     */
    public function pack($signature, ...$params): Generator
    {
        $output = '';

        //structure
        $length = count($params);
        if ($length < self::SMALL) { //TINY_STRUCT
            $output .= pack('C', 0b10110000 | $length);
        } elseif ($length < self::MEDIUM) { //STRUCT_8
            $output .= chr(0xDC) . pack('C', $length);
        } elseif ($length < self::LARGE) { //STRUCT_16
            $output .= chr(0xDD) . pack('n', $length);
        } else {
            throw new PackException('Too many parameters');
        }

        $output .= chr($signature);

        foreach ($params as $param) {
            $output .= $this->p($param);
        }

        //structure buffer
        $len = mb_strlen($output, '8bit');
        for ($i = 0; $i < $len; $i += 65535) {
            $chunk = mb_substr($output, $i, 65535, '8bit');
            yield pack('n', mb_strlen($chunk, '8bit')) . $chunk;
        }

        yield chr(0x00) . chr(0x00);
    }

    /**
     * @param mixed $param
     * @return string
     * @throws PackException
     */
    private function p($param): string
    {
        $output = '';
        if (is_int($param)) {
            $output .= $this->packInteger($param);
        } elseif (is_float($param)) {
            $output .= $this->packFloat($param);
        } elseif (is_null($param)) {
            $output .= chr(0xC0);
        } elseif (is_bool($param)) {
            $output .= chr($param ? 0xC3 : 0xC2);
        } elseif (is_string($param)) {
            $output .= $this->packString($param);
        } elseif (is_array($param)) {
            $keys = array_keys($param);
            if (count($param) == 0 || count(array_filter($keys, 'is_int')) == count($keys)) {
                $output .= $this->packList($param);
            } else {
                $output .= $this->packMap($param);
            }
        } elseif (is_object($param)) {
            $output .= $this->packMap((array)$param);
        } else {
            throw new PackException('Not recognized type of parameter');
        }

        return $output;
    }

    /**
     * @param string $str
     * @return string
     * @throws PackException
     */
    private function packString(string $str): string
    {
        $output = '';
        $length = mb_strlen($str, '8bit');

        if ($length < self::SMALL) { //TINY_STRING
            $output .= pack('C', 0b10000000 | $length) . $str;
        } elseif ($length < self::MEDIUM) { //STRING_8
            $output .= chr(0xD0) . pack('C', $length) . $str;
        } elseif ($length < self::LARGE) { //STRING_16
            $output .= chr(0xD1) . pack('n', $length) . $str;
        } elseif ($length < self::HUGE) { //STRING_32
            $output .= chr(0xD2) . pack('N', $length) . $str;
        } else {
            throw new PackException('String too long');
        }

        return $output;
    }

    /**
     * @param float $value
     * @return string
     */
    private function packFloat(float $value): string
    {
        return chr(0xC1) . strrev(pack('d', $value));
    }

    /**
     * @param int $value
     * @return string
     * @throws PackException
     */
    private function packInteger(int $value): string
    {
        $output = '';
        $tmp = unpack('S', "\x01\x00");
        $little = $tmp[1] == 1;

        if ($value >= 0 && $value <= 127) { //+TINY_INT
            $packed = pack('C', 0b00000000 | $value);
            $output .= $little ? strrev($packed) : $packed;
        } elseif ($value >= -16 && $value < 0) { //-TINY_INT
            $packed = pack('c', 0b11110000 | $value);
            $output .= $little ? strrev($packed) : $packed;
        } elseif ($value >= -128 && $value <= -17) { //INT_8
            $packed = pack('c', $value);
            $output .= chr(0xC8) . ($little ? strrev($packed) : $packed);
        } elseif (($value >= 128 && $value <= 32767) || ($value >= -32768 && $value <= -129)) { //INT_16
            $packed = pack('s', $value);
            $output .= chr(0xC9) . ($little ? strrev($packed) : $packed);
        } elseif (($value >= 32768 && $value <= 2147483647) || ($value >= -2147483648 && $value <= -32769)) { //INT_32
            $packed = pack('l', $value);
            $output .= chr(0xCA) . ($little ? strrev($packed) : $packed);
        } elseif (($value >= 2147483648 && $value <= 9223372036854775807) || ($value >= -9223372036854775808 && $value <= -2147483649)) { //INT_64
            $packed = pack('q', $value);
            $output .= chr(0xCB) . ($little ? strrev($packed) : $packed);
        } else {
            throw new PackException('Integer out of range');
        }

        return $output;
    }

    /**
     * @param array $arr
     * @return string
     * @throws PackException
     */
    private function packMap(array $arr): string
    {
        $output = '';
        $size = count($arr);

        if ($size < self::SMALL) { //TINY_MAP
            $output .= pack('C', 0b10100000 | $size);
        } elseif ($size < self::MEDIUM) { //MAP_8
            $output .= chr(0xD8) . pack('C', $size);
        } elseif ($size < self::LARGE) { //MAP_16
            $output .= chr(0xD9) . pack('n', $size);
        } elseif ($size < self::HUGE) { //MAP_32
            $output .= chr(0xDA) . pack('N', $size);
        } else {
            throw new PackException('Too many map elements');
        }

        foreach ($arr as $k => $v) {
            $output .= $this->p((string)$k); // The key names in a map must be of type String.
            $output .= $this->p($v);
        }

        return $output;
    }

    /**
     * @param array $arr
     * @return string
     * @throws PackException
     */
    private function packList(array $arr): string
    {
        $output = '';
        $size = count($arr);

        if ($size < self::SMALL) { //TINY_LIST
            $output .= pack('C', 0b10010000 | $size);
        } elseif ($size < self::MEDIUM) { //LIST_8
            $output .= chr(0xD4) . pack('C', $size);
        } elseif ($size < self::LARGE) { //LIST_16
            $output .= chr(0xD5) . pack('n', $size);
        } elseif ($size < self::HUGE) { //LIST_32
            $output .= chr(0xD6) . pack('N', $size);
        } else {
            throw new PackException('Too many list elements');
        }

        foreach ($arr as $v) {
            $output .= $this->p($v);
        }

        return $output;
    }

}

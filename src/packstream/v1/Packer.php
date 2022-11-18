<?php

namespace Bolt\packstream\v1;

use Bolt\error\PackException;
use Bolt\packstream\{Bytes, IPackDictionaryGenerator, IPackListGenerator, IPacker};
use Bolt\protocol\IStructure;

/**
 * Class Packer of PackStream version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\packstream\v1
 */
class Packer implements IPacker
{
    private const SMALL = 16;
    private const MEDIUM = 256;
    private const LARGE = 65536;
    private const HUGE = 4294967295;

    private bool $littleEndian;
    private array $structuresLt = [];

    /**
     * @inheritDoc
     */
    public function setAvailableStructures(array $structures)
    {
        $this->structuresLt = $structures;
    }

    /**
     * Pack message with parameters
     * @param $signature
     * @param mixed ...$params
     * @return iterable
     * @throws PackException
     */
    public function pack($signature, ...$params): iterable
    {
        $this->littleEndian = unpack('S', "\x01\x00")[1] === 1;

        //structure
        $length = count($params);
        if ($length < self::SMALL) { //TINY_STRUCT
            yield pack('n', 1) . pack('C', 0b10110000 | $length);
        } elseif ($length < self::MEDIUM) { //STRUCT_8
            yield pack('n', 2) . chr(0xDC) . pack('C', $length);
        } elseif ($length < self::LARGE) { //STRUCT_16
            yield pack('n', 4) . chr(0xDD) . pack('n', $length);
        } else {
            throw new PackException('Too many parameters');
        }

        yield pack('n', 1) . chr($signature);

        foreach ($params as $param) {
            foreach ($this->p($param) as $packed) {
                $totalLength = mb_strlen($packed, '8bit');
                $offset = 0;
                while ($offset < $totalLength) {
                    $chunk = mb_strcut($packed, $offset, 65535, '8bit');
                    $chunkLength = mb_strlen($chunk, '8bit');
                    $offset += $chunkLength;
                    yield pack('n', $chunkLength) . $chunk;
                }
            }
        }

        yield chr(0x00) . chr(0x00);
    }

    /**
     * @param mixed $param
     * @return iterable
     * @throws PackException
     */
    private function p($param): iterable
    {
        switch (gettype($param)) {
            case 'integer':
                yield from $this->packInteger($param);
                break;
            case 'double':
                yield from $this->packFloat($param);
                break;
            case 'boolean':
                yield chr($param ? 0xC3 : 0xC2);
                break;
            case 'NULL':
                yield chr(0xC0);
                break;
            case 'string':
                yield from $this->packString($param);
                break;
            case 'array':
                if ($param === array_values($param)) {
                    yield from $this->packList($param);
                } else {
                    yield from $this->packDictionary($param);
                }
                break;
            case 'object':
                if ($param instanceof IStructure) {
                    yield from $this->packStructure($param);
                } elseif ($param instanceof Bytes) {
                    yield from $this->packByteArray($param);
                } elseif ($param instanceof IPackListGenerator) {
                    yield from $this->packList($param);
                } elseif ($param instanceof IPackDictionaryGenerator) {
                    yield from $this->packDictionary($param);
                } else {
                    yield from $this->packDictionary((array)$param);
                }
                break;

            default:
                throw new PackException('Not recognized type of parameter');
        }
    }

    /**
     * @param string $str
     * @return iterable
     * @throws PackException
     */
    private function packString(string $str): iterable
    {
        $length = mb_strlen($str, '8bit');

        if ($length < self::SMALL) { //TINY_STRING
            yield pack('C', 0b10000000 | $length) . $str;
        } elseif ($length < self::MEDIUM) { //STRING_8
            yield chr(0xD0) . pack('C', $length) . $str;
        } elseif ($length < self::LARGE) { //STRING_16
            yield chr(0xD1) . pack('n', $length) . $str;
        } elseif ($length < self::HUGE) { //STRING_32
            yield chr(0xD2) . pack('N', $length) . $str;
        } else {
            throw new PackException('String too long');
        }
    }

    /**
     * @param float $value
     * @return iterable
     */
    private function packFloat(float $value): iterable
    {
        $packed = pack('d', $value);
        yield chr(0xC1) . ($this->littleEndian ? strrev($packed) : $packed);
    }

    /**
     * @param int $value
     * @return iterable
     * @throws PackException
     */
    private function packInteger(int $value): iterable
    {
        if ($value >= -16 && $value <= 127) { //TINY_INT
            yield pack('c', $value);
        } elseif ($value >= -128 && $value <= -17) { //INT_8
            yield chr(0xC8) . pack('c', $value);
        } elseif (($value >= 128 && $value <= 32767) || ($value >= -32768 && $value <= -129)) { //INT_16
            $packed = pack('s', $value);
            yield chr(0xC9) . ($this->littleEndian ? strrev($packed) : $packed);
        } elseif (($value >= 32768 && $value <= 2147483647) || ($value >= -2147483648 && $value <= -32769)) { //INT_32
            $packed = pack('l', $value);
            yield chr(0xCA) . ($this->littleEndian ? strrev($packed) : $packed);
        } elseif (($value >= 2147483648 && $value <= 9223372036854775807) || ($value >= -9223372036854775808 && $value <= -2147483649)) { //INT_64
            $packed = pack('q', $value);
            yield chr(0xCB) . ($this->littleEndian ? strrev($packed) : $packed);
        } else {
            throw new PackException('Integer out of range');
        }
    }

    /**
     * @param array|IPackDictionaryGenerator $param
     * @return iterable
     * @throws PackException
     */
    private function packDictionary($param): iterable
    {
        $size = is_array($param) ? count($param) : $param->count();

        if ($size < self::SMALL) { //TINY_MAP
            yield pack('C', 0b10100000 | $size);
        } elseif ($size < self::MEDIUM) { //MAP_8
            yield chr(0xD8) . pack('C', $size);
        } elseif ($size < self::LARGE) { //MAP_16
            yield chr(0xD9) . pack('n', $size);
        } elseif ($size < self::HUGE) { //MAP_32
            yield chr(0xDA) . pack('N', $size);
        } else {
            throw new PackException('Too many map elements');
        }

        foreach ($param as $k => $v) {
            yield from $this->p((string)$k); // The key names in a map must be of type String.
            yield from $this->p($v);
        }
    }

    /**
     * @param array|IPackListGenerator $param
     * @return iterable
     * @throws PackException
     */
    private function packList($param): iterable
    {
        $size = is_array($param) ? count($param) : $param->count();

        if ($size < self::SMALL) { //TINY_LIST
            yield pack('C', 0b10010000 | $size);
        } elseif ($size < self::MEDIUM) { //LIST_8
            yield chr(0xD4) . pack('C', $size);
        } elseif ($size < self::LARGE) { //LIST_16
            yield chr(0xD5) . pack('n', $size);
        } elseif ($size < self::HUGE) { //LIST_32
            yield chr(0xD6) . pack('N', $size);
        } else {
            throw new PackException('Too many list elements');
        }

        foreach ($param as $v) {
            yield from $this->p($v);
        }
    }

    /**
     * @param IStructure $structure
     * @return iterable
     * @throws PackException
     */
    private function packStructure(IStructure $structure): iterable
    {
        $signature = array_search(get_class($structure), $this->structuresLt);
        if ($signature === false) {
            throw new PackException('Provided structure as parameter is not supported');
        }

        $reflection = new \ReflectionClass($structure);

        yield pack('C', 0b10110000 | $reflection->getConstructor()->getNumberOfParameters()) . chr($signature);

        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (is_null($type)) {
                throw new PackException('Undefined parameter type in structure ' . $structure);
            }

            $packerMethod = $type->getName();
            if ($packerMethod === 'int') {
                $packerMethod = 'integer';
            }
            $packerMethod = 'pack' . ucfirst($packerMethod);

            $methodName = $parameter->getName();
            yield from $this->{$packerMethod}($structure->{$methodName}());
        }
    }

    /**
     * @param Bytes $bytes
     * @return iterable
     * @throws PackException
     */
    private function packByteArray(Bytes $bytes): iterable
    {
        $size = count($bytes);
        if ($size < self::MEDIUM) {
            yield chr(0xCC) . pack('C', $size) . $bytes;
        } elseif ($size < self::LARGE) {
            yield chr(0xCD) . pack('n', $size) . $bytes;
        } elseif ($size <= 2147483647) {
            yield chr(0xCE) . pack('N', $size) . $bytes;
        } else {
            throw new PackException('ByteArray too big');
        }
    }

}

<?php

namespace Bolt;

use Bolt\structures\{Node, Path, Relationship, UnboundRelationship};

/**
 * Class Unpacker
 * Unpack bolt messages
 *
 * @author Michal Stefanak
 */
class Unpacker
{
    /**
     * Unpack message
     * @param string $msg
     * @param int &$signature
     * @return mixed
     * @throws Exception
     */
    public function unpack(string $msg, int &$signature = 0)
    {
        if (empty($msg)) {
            return null;
        }

        $size = 0;
        $offset = 0;
        $marker = ord($msg[0]);
        if ($marker == 0xDC) { //STRUCT_8
            $size = unpack('C', $msg[1]);
            $offset = 2;
        } elseif ($marker == 0xDD) { //STRUCT_16
            $size = unpack('n', $msg[1] . $msg[2]);
            $offset = 3;
        } elseif ($marker >> 4 == 0b1011) { //TINY_STRUCT
            $size = 0b10110000 ^ $marker;
            $offset = 1;
        }

        $signature = ord($msg[$offset]);
        $msg = mb_strcut($msg, $offset + 1, null, '8bit');
        return $this->u($msg);
    }

    /**
     * @param string $msg
     * @return mixed
     * @throws Exception
     */
    private function u(string &$msg)
    {
        if (empty($msg)) {
            return false;
        }
        
        $marker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $result = false;

        $output = $this->unpackStruct($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackNode($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackRelationship($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackPath($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackUnboundRelationship($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackFloat($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackString($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackList($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackMap($marker, $msg, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackInteger($marker, $msg, $result);
        if ($result) {
            return $output;
        }

        return null;
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return mixed|null
     * @throws Exception
     */
    private function unpackStruct(int $marker, string &$msg, bool &$result = false)
    {
        $size = 0;
        $offset = 0;
        if ($marker == 0xDC) { //STRUCT_8
            $size = unpack('C', $msg[1]);
            $offset = 1;
            $result = true;
        } elseif ($marker == 0xDD) { //STRUCT_16
            $size = unpack('n', $msg[1] . $msg[2]);
            $offset = 2;
            $result = true;
        } elseif ($marker >> 4 == 0b1011) { //TINY_STRUCT
            $size = 0b10110000 ^ $marker;
            $result = true;
        }

        if ($result) {
            $msg = mb_strcut($msg, $offset, null, '8bit');
            return $this->u($msg);
        }

        return null;
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return Node|null
     * @throws Exception
     */
    private function unpackNode(int $marker, string &$msg, bool &$result = false): ?Node
    {
        if ($marker != 0x4E) {
            return null;
        }

        $identityMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $identity = $this->unpackInteger($identityMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Node structure identifier unpack error');
        }

        $labelsMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $labels = $this->unpackList($labelsMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Node structure labels unpack error');
        }

        $propertiesMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $properties = $this->unpackMap($propertiesMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Node structure properties unpack error');
        }

        return new Node($identity, $labels, $properties);
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return Relationship|null
     * @throws Exception
     */
    private function unpackRelationship(int $marker, string &$msg, bool &$result = false): ?Relationship
    {
        if ($marker != 0x52) {
            return null;
        }

        $identityMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $identity = $this->unpackInteger($identityMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Relationship structure identifier unpack error');
        }

        $startNodeIdentityMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $startNodeIdentity = $this->unpackInteger($startNodeIdentityMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Relationship structure start node identifier unpack error');
        }

        $endNodeIdentityMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $endNodeIdentity = $this->unpackInteger($endNodeIdentityMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Relationship structure end node identifier unpack error');
        }

        $typeMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $type = $this->unpackString($typeMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Relationship structure type unpack error');
        }

        $propertiesMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $properties = $this->unpackMap($propertiesMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Relationship structure properties unpack error');
        }

        return new Relationship($identity, $startNodeIdentity, $endNodeIdentity, $type, $properties);
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return UnboundRelationship|null
     * @throws Exception
     */
    private function unpackUnboundRelationship(int $marker, string &$msg, bool &$result = false): ?UnboundRelationship
    {
        if ($marker != 0x72) {
            return null;
        }

        $identityMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $identity = $this->unpackInteger($identityMarker, $msg, $result);
        if (!$result) {
            throw new Exception('UnboundRelationship structure identifier unpack error');
        }

        $typeMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $type = $this->unpackString($typeMarker, $msg, $result);
        if (!$result) {
            throw new Exception('UnboundRelationship structure type unpack error');
        }

        $propertiesMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $properties = $this->unpackMap($propertiesMarker, $msg, $result);
        if (!$result) {
            throw new Exception('UnboundRelationship structure properties unpack error');
        }

        return new UnboundRelationship($identity, $type, $properties);
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return Path|null
     * @throws Exception
     */
    private function unpackPath(int $marker, string &$msg, bool &$result = false): ?Path
    {
        if ($marker != 0x50) {
            return null;
        }

        $nodesMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $nodes = $this->unpackList($nodesMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Path structure nodes unpack error');
        }

        $relationshipsMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $relationships = $this->unpackList($relationshipsMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Path structure relationships unpack error');
        }

        $sequenceMarker = ord($msg[0]);
        $msg = mb_strcut($msg, 1, null, '8bit');
        $sequence = $this->unpackList($sequenceMarker, $msg, $result);
        if (!$result) {
            throw new Exception('Path structure sequence unpack error');
        }

        return new Path($nodes, $relationships, $sequence);
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return array
     * @throws Exception
     */
    private function unpackMap(int $marker, string &$msg, bool &$result = false): array
    {
        $size = -1;
        $offset = 0;
        if ($marker >> 4 == 0b1010) { //TINY_MAP
            $size = 0b10100000 ^ $marker;
        } elseif ($marker == 0xD8) { //MAP_8
            $size = unpack('C', $msg[0])[1] ?? $size;
            $offset = 1;
        } elseif ($marker == 0xD9) { //MAP_16
            $size = unpack('n', $msg[0] . $msg[1])[1] ?? $size;
            $offset = 2;
        } elseif ($marker == 0xDA) { //MAP_32
            $size = unpack('N', mb_strcut($msg, 0, 4, '8bit'))[1] ?? $size;
            $offset = 4;
        }

        $output = [];
        if ($size != -1) {
            $msg = mb_strcut($msg, $offset, null, '8bit');
            $key = null;
            for ($i = 0; $i < $size * 2; $i++) {
                if ($i % 2 == 0) {
                    $key = $this->u($msg);
                } else {
                    $output[$key] = $this->u($msg);
                }
            }
            $result = true;
        }

        return $output;
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return string
     */
    private function unpackString(int $marker, string &$msg, bool &$result = false): string
    {
        $length = -1;
        $offset = 0;
        if ($marker >> 4 == 0b1000) { //TINY_STRING
            $length = 0b10000000 ^ $marker;
        } elseif ($marker == 0xD0) { //STRING_8
            $length = unpack('C', $msg[0])[1] ?? $length;
            $offset = 1;
        } elseif ($marker == 0xD1) { //STRING_16
            $length = unpack('n', $msg[0] . $msg[1])[1] ?? $length;
            $offset = 2;
        } elseif ($marker == 0xD2) { //STRING_32
            $length = unpack('N', mb_strcut($msg, 0, 4, '8bit'))[1] ?? $length;
            $offset = 4;
        }

        $output = '';
        if ($length != -1) {
            $output = mb_strcut($msg, $offset, $length, '8bit');
            $msg = mb_strcut($msg, $offset + $length, null, '8bit');
            $result = true;
        }

        return $output;
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return int
     */
    private function unpackInteger(int $marker, string &$msg, bool &$result = false): int
    {
        $output = null;
        $offset = 0;

        if ($marker >> 7 == 0b0) { //+TINY_INT
            $output = $marker;
        } elseif ($marker >> 4 == 0b1111) { //-TINY_INT
            $output = 0b11110000 ^ $marker;
        } elseif ($marker == 0xC8) { //INT_8
            $output = unpack('c', $msg[0])[1] ?? 0;
            $offset = 1;
        } elseif ($marker == 0xC9) { //INT_16
            $output = unpack('s', $this->bigEndian($msg[0] . $msg[1]))[1] ?? 0;
            $offset = 2;
        } elseif ($marker == 0xCA) { //INT_32
            $output = unpack('l', $this->bigEndian(mb_strcut($msg, 0, 4, '8bit')))[1] ?? 0;
            $offset = 4;
        } elseif ($marker == 0xCB) { //INT_64
            $output = unpack('q', mb_strcut($msg, 0, 8, '8bit'))[1] ?? 0;
            $offset = 8;
        }

        if ($output !== null) {
            if ($offset > 0) {
                $msg = mb_strcut($msg, $offset, null, '8bit');
            }
            $result = true;
        }
        return (int)$output;
    }
    
    /**
     * Fix little endian
     * @param string $str
     * @return string
     */
    private function bigEndian($str)
    {
        $tmp = unpack('S', "\x01\x00");
        $little = $tmp[1] == 1;

        return $little ? strrev($str) : $str;
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return float
     */
    private function unpackFloat(int $marker, string &$msg, bool &$result = false): float
    {
        $output = 0;
        if ($marker == 0xC1) {
            $output = unpack('d', strrev(mb_strcut($msg, 0, 8, '8bit')))[1] ?? 0;
            $result = true;
        }
        return $output;
    }

    /**
     * @param int $marker
     * @param string $msg
     * @param bool $result
     * @return array
     * @throws Exception
     */
    private function unpackList(int $marker, string &$msg, bool &$result = false): array
    {
        $size = -1;
        $offset = 0;
        if ($marker >> 4 == 0b1001) { //TINY_LIST
            $size = 0b10010000 ^ $marker;
        } elseif ($marker == 0xD4) { //LIST_8
            $size = unpack('C', $msg[0])[1] ?? $size;
            $offset = 1;
        } elseif ($marker == 0xD5) { //LIST_16
            $size = unpack('n', $msg[0] . $msg[1])[1] ?? $size;
            $offset = 2;
        } elseif ($marker == 0xD6) { //LIST_32
            $size = unpack('N', mb_strcut($msg, 0, 4, '8bit'))[1] ?? $size;
            $offset = 4;
        }

        $output = [];
        if ($size != -1) {
            $msg = mb_strcut($msg, $offset, null, '8bit');
            for ($i = 0; $i < $size; $i++) {
                $output[] = $this->u($msg);
            }
            $result = true;
        }

        return $output;
    }
    
}

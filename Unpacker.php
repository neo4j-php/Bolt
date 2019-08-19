<?php

namespace Bolt;

use Bolt\structures\{
    Node,
    Path,
    Relationship,
    UnboundRelationship
};
use Exception;

/**
 * Class Unpacker
 * Unpack bolt messages
 *
 * @author Michal Stefanak
 */
class Unpacker
{
    /**
     * @var string
     */
    private $message;

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
     * @throws Exception
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
     * @throws Exception
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
        
        $output = $this->unpackNode($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackRelationship($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackPath($marker, $result);
        if ($result) {
            return $output;
        }
        $output = $this->unpackUnboundRelationship($marker, $result);
        if ($result) {
            return $output;
        }

        return null;
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return Node|null
     * @throws Exception
     */
    private function unpackNode(int $marker, bool &$result = false): ?Node
    {
        if ($marker != 0x4E) {
            return null;
        }

        $identityMarker = ord($this->next(1));
        $identity = $this->unpackInteger($identityMarker, $result);
        if (!$result) {
            throw new Exception('Node structure identifier unpack error');
        }

        $labelsMarker = ord($this->next(1));
        $labels = $this->unpackList($labelsMarker, $result);
        if (!$result) {
            throw new Exception('Node structure labels unpack error');
        }

        $propertiesMarker = ord($this->next(1));
        $properties = $this->unpackMap($propertiesMarker, $result);
        if (!$result) {
            throw new Exception('Node structure properties unpack error');
        }

        return new Node($identity, $labels, $properties);
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return Relationship|null
     * @throws Exception
     */
    private function unpackRelationship(int $marker, bool &$result = false): ?Relationship
    {
        if ($marker != 0x52) {
            return null;
        }

        $identityMarker = ord($this->next(1));
        $identity = $this->unpackInteger($identityMarker, $result);
        if (!$result) {
            throw new Exception('Relationship structure identifier unpack error');
        }

        $startNodeIdentityMarker = ord($this->next(1));
        $startNodeIdentity = $this->unpackInteger($startNodeIdentityMarker, $result);
        if (!$result) {
            throw new Exception('Relationship structure start node identifier unpack error');
        }

        $endNodeIdentityMarker = ord($this->next(1));
        $endNodeIdentity = $this->unpackInteger($endNodeIdentityMarker, $result);
        if (!$result) {
            throw new Exception('Relationship structure end node identifier unpack error');
        }

        $typeMarker = ord($this->next(1));
        $type = $this->unpackString($typeMarker, $result);
        if (!$result) {
            throw new Exception('Relationship structure type unpack error');
        }

        $propertiesMarker = ord($this->next(1));
        $properties = $this->unpackMap($propertiesMarker, $result);
        if (!$result) {
            throw new Exception('Relationship structure properties unpack error');
        }

        return new Relationship($identity, $startNodeIdentity, $endNodeIdentity, $type, $properties);
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return UnboundRelationship|null
     * @throws Exception
     */
    private function unpackUnboundRelationship(int $marker, bool &$result = false): ?UnboundRelationship
    {
        if ($marker != 0x72) {
            return null;
        }

        $identityMarker = ord($this->next(1));
        $identity = $this->unpackInteger($identityMarker, $result);
        if (!$result) {
            throw new Exception('UnboundRelationship structure identifier unpack error');
        }

        $typeMarker = ord($this->next(1));
        $type = $this->unpackString($typeMarker, $result);
        if (!$result) {
            throw new Exception('UnboundRelationship structure type unpack error');
        }

        $propertiesMarker = ord($this->next(1));
        $properties = $this->unpackMap($propertiesMarker, $result);
        if (!$result) {
            throw new Exception('UnboundRelationship structure properties unpack error');
        }

        return new UnboundRelationship($identity, $type, $properties);
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return Path|null
     * @throws Exception
     */
    private function unpackPath(int $marker, bool &$result = false): ?Path
    {
        if ($marker != 0x50) {
            return null;
        }

        $nodesMarker = ord($this->next(1));
        $nodes = $this->unpackList($nodesMarker, $result);
        if (!$result) {
            throw new Exception('Path structure nodes unpack error');
        }

        $relationshipsMarker = ord($this->next(1));
        $relationships = $this->unpackList($relationshipsMarker, $result);
        if (!$result) {
            throw new Exception('Path structure relationships unpack error');
        }

        $sequenceMarker = ord($this->next(1));
        $sequence = $this->unpackList($sequenceMarker, $result);
        if (!$result) {
            throw new Exception('Path structure sequence unpack error');
        }

        return new Path($nodes, $relationships, $sequence);
    }

    /**
     * @param int $marker
     * @param bool $result
     * @return array
     * @throws Exception
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
            $output = 0b11110000 ^ $marker;
        } elseif ($marker == 0xC8) { //INT_8
            $output = unpack('c', $this->next(1))[1] ?? 0;
        } elseif ($marker == 0xC9) { //INT_16
            $value = $this->next(2);
            $value = $little ? strrev($value) : $value;
            $output = unpack('s', $value)[1] ?? 0;
        } elseif ($marker == 0xCA) { //INT_32
            $value = $this->next(4);
            $value = $little ? strrev($value) : $value;
            $output = unpack('l', $value)[1] ?? 0;
        } elseif ($marker == 0xCB) { //INT_64
            $output = unpack('q', $this->next(8))[1] ?? 0;
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
     * @throws Exception
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

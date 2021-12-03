<?php

namespace Bolt\protocol;

use Bolt\error\MessageException;
use Bolt\error\PackException;

/**
 * Class Protocol version 4.3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-4.html#version-43
 * @package Bolt\protocol
 */
class V4_3 extends V4_2
{
    /**
     * @param array|string|null ...$args
     * @return array
     * @throws MessageException
     * @throws PackException
     */
    public function route(...$args): array
    {
        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x66, (object) $args[0], $args[1] ?? [], $args[2] ?? null));

        $signature = 0;
        $output = $this->read($signature);

        if ($signature === self::FAILURE) {
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature === self::SUCCESS ? $output : [];
    }
}

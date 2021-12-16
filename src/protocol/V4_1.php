<?php

namespace Bolt\protocol;

use Bolt\error\PackException;
use Bolt\error\MessageException;

/**
 * Class Protocol version 4.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-4.html#version-41
 * @package Bolt\protocol
 */
class V4_1 extends V4
{

    /**
     * @inheritDoc
     */
    public function hello(...$args): array
    {
        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $args[0]['routing'] = array_key_exists('routing', $args[0]) && is_array($args[0]['routing']) ? (object)$args[1] : null;
        $this->write($this->packer->pack(0x01, $args[0]));

        $signature = 0;
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $output;
    }

}

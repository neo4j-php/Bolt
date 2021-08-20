<?php

namespace Bolt\protocol;

use Bolt\error\MessageException;
use Bolt\error\PackException;
use function count;
use function is_array;

/**
 * Class Protocol version 4.3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
class V4_3 extends V4_1
{
    public function route(?array $routing = null): array
    {
        $this->write($this->packer->pack(0x66, $routing ?? []));

        $signature = 0;
        $output = $this->read($signature);

        if ($signature === self::FAILURE) {
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature === self::SUCCESS ? $output : [];
    }
}

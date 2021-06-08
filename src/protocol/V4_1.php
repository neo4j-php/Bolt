<?php

namespace Bolt\protocol;

use Bolt\error\PackException;
use Exception;
use Bolt\error\MessageException;

/**
 * Class Protocol version 4.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
class V4_1 extends V4
{

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function hello(...$args): array
    {
        if (count($args) < 5) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x01, [
            'user_agent' => $args[0],
            'scheme' => $args[1],
            'principal' => $args[2],
            'credentials' => $args[3],
            'routing' => is_array($args[4]) ? (object)$args[4] : null
        ]));

        $signature = 0;
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS ? $output : [];
    }

}

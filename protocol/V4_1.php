<?php

namespace Bolt\protocol;

use Bolt\Bolt;
use Exception;

/**
 * Class Protocol version 4.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
class V4_1 extends V4
{

    public function hello(...$args): bool
    {
        if (count($args) < 4) {
            Bolt::error('Wrong arguments count');
            return false;
        }

        try {
            $msg = $this->packer->pack(0x01, [
                'user_agent' => $args[0],
                'scheme' => $args[1],
                'principal' => $args[2],
                'credentials' => $args[3],
                'routing' => (object)($args[4] ?? [])
            ]);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        $this->socket->write($msg);

        list($signature, $output) = $this->socket->read($this->unpacker);
        if ($signature == self::FAILURE) {
            try {
                $msg = $this->packer->pack(0x0E);
            } catch (Exception $ex) {
                Bolt::error($msg);
                return false;
            }

            $this->socket->write($msg);
            Bolt::error($output['message'], $output['code']);
        }

        return $signature == self::SUCCESS;
    }

}

<?php

namespace Bolt\protocol;

use Bolt\Bolt;
use Bolt\Socket;
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

        Socket::write($msg);

        list($signature, $output) = Socket::read($this->unpacker);
        if ($signature == self::FAILURE) {
            try {
                $msg = $this->packer->pack(0x0E);
            } catch (Exception $ex) {
                Bolt::error($msg);
                return false;
            }

            Socket::write($msg);
            Bolt::error($output['message'], $output['code']);
        }

        return $signature == self::SUCCESS;
    }

}

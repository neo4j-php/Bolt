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
        if (count($args) < 5) {
            Bolt::error('Wrong arguments count');
            return false;
        }

        try {
            $msg = $this->packer->pack(0x01, [
                'user_agent' => $args[0],
                'scheme' => $args[1],
                'principal' => $args[2],
                'credentials' => $args[3],
                'routing' => is_array($args[4]) ? (object)$args[4] : null
            ]);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        $this->write($msg);

        $signature = 0;
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            try {
                $msg = $this->packer->pack(0x0E);
            } catch (Exception $ex) {
                Bolt::error($msg);
                return false;
            }

            $this->write($msg);
            Bolt::error($output['message'], $output['code']);
        }

        return $signature == self::SUCCESS;
    }

}

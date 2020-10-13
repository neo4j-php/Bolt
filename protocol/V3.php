<?php

namespace Bolt\protocol;

use Bolt\Bolt;
use Bolt\Socket;
use Exception;

/**
 * Class Protocol version 3
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
class V3 extends V2
{

    public function init(...$args): bool
    {
        return $this->hello(...$args);
    }

    public function hello(...$args): bool
    {
        if (count($args) != 4) {
            Bolt::error('Wrong arguments count');
            return false;
        }

        try {
            $msg = $this->packer->pack(0x01, [
                'user_agent' => $args[0],
                'scheme' => $args[1],
                'principal' => $args[2],
                'credentials' => $args[3]
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

    public function run(...$args)
    {
        if (empty($args)) {
            Bolt::error('Wrong arguments count');
            return false;
        }

        try {
            $msg = $this->packer->pack(0x10, $args[0], (object)($args[1] ?? []), (object)($args[2] ?? []));
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature, $output) = Socket::read($this->unpacker);
        if ($signature == self::FAILURE) {
            $this->reset();
            Bolt::error($output['message'], $output['code']);
        }
        return $signature == self::SUCCESS ? $output : false;
    }

    public function reset(...$args)
    {
        try {
            $msg = $this->packer->pack(0x0F);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);
    }

    public function begin(...$args): bool
    {
        try {
            $msg = $this->packer->pack(0x11, (object)($args[0] ?? []));
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature, $output) = Socket::read($this->unpacker);
        if ($signature == self::FAILURE) {
            $this->reset();
            Bolt::error($output['message'], $output['code']);
        }
        return $signature == self::SUCCESS;
    }

    public function commit(...$args): bool
    {
        try {
            $msg = $this->packer->pack(0x12);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature, $output) = Socket::read($this->unpacker);
        if ($signature == self::FAILURE) {
            $this->reset();
            Bolt::error($output['message'], $output['code']);
        }
        return $signature == self::SUCCESS;
    }

    public function rollback(...$args): bool
    {
        try {
            $msg = $this->packer->pack(0x13);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature, $output) = Socket::read($this->unpacker);
        if ($signature == self::FAILURE) {
            $this->reset();
            Bolt::error($output['message'], $output['code']);
        }
        return $signature == self::SUCCESS;
    }

    public function goodbye(...$args)
    {
        try {
            $msg = $this->packer->pack(0x02);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);
    }

}

<?php

namespace Bolt\protocol;

use Bolt\Bolt;
use Bolt\Socket;
use Exception;

/**
 * Class Protocol version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
class V1 extends AProtocol
{

    public function init(...$args): bool
    {
        if (count($args) != 4) {
            Bolt::error('Wrong arguments count');
            return false;
        }

        try {
            $msg = $this->packer->pack(0x01, $args[0], [
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

            //AckFailure after init do not respond with any message
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
            $msg = $this->packer->pack(0x10, $args[0], $args[1] ?? []);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature, $output) = Socket::read($this->unpacker);
        if ($signature == self::FAILURE) {
            $this->ackFailure();
            Bolt::error($output['message'], $output['code']);
        }
        return $signature == self::SUCCESS ? $output : false;
    }

    public function pullAll(...$args)
    {
        try {
            $msg = $this->packer->pack(0x3F);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        $output = [];
        do {
            list($signature, $ret) = Socket::read($this->unpacker);
            $output[] = $ret;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $this->ackFailure();
            Bolt::error($ret['message'], $ret['code']);
            $output = false;
        }

        return $output;
    }

    public function discardAll(...$args): bool
    {
        try {
            $msg = $this->packer->pack(0x2F);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature,) = Socket::read($this->unpacker);
        return $signature == self::SUCCESS;
    }

    /*
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     */
    private function ackFailure(): bool
    {
        try {
            $msg = $this->packer->pack(0x0E);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature,) = Socket::read($this->unpacker);
        return $signature == self::SUCCESS;
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

        list($signature,) = Socket::read($this->unpacker);
        return $signature == self::SUCCESS;
    }

}

<?php

namespace Bolt\protocol;

use Bolt\error\MessageException;
use Bolt\error\PackException;
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

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function init(...$args): array
    {
        if (count($args) < 4) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x01, $args[0], [
            'scheme' => $args[1],
            'principal' => $args[2],
            'credentials' => $args[3]
        ]));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            //AckFailure after init do not respond with any message
            $this->write($this->packer->pack(0x0E));
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS ? $output : [];
    }

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function run(...$args)
    {
        if (empty($args)) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x10, $args[0], $args[1] ?? []));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->ackFailure();
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS ? $output : [];
    }

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function pullAll(...$args)
    {
        $this->write($this->packer->pack(0x3F));

        $output = [];
        do {
            $ret = $this->read($signature);
            $output[] = $ret;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $this->ackFailure();
            $last = array_pop($output);
            throw new MessageException($last['message'] . ' (' . $last['code'] . ')');
        }

        return $signature == self::SUCCESS ? $output : [];
    }

    /**
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function discardAll(...$args): bool
    {
        $this->write($this->packer->pack(0x2F));
        $this->read($signature);

        return $signature == self::SUCCESS;
    }

    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     *
     * @return bool
     * @throws Exception
     */
    private function ackFailure(): bool
    {
        $this->write($this->packer->pack(0x0E));
        $this->read($signature);

        return $signature == self::SUCCESS;
    }

    /**
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function reset(...$args): bool
    {
        $this->write($this->packer->pack(0x0F));
        $this->read($signature);

        return $signature == self::SUCCESS;
    }

}

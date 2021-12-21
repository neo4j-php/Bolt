<?php

namespace Bolt\protocol;

use Bolt\error\MessageException;
use Bolt\error\PackException;
use Exception;

/**
 * Class Protocol version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-1.html
 * @package Bolt\protocol
 */
class V1 extends AProtocol
{

    /**
     * Send INIT message
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---init
     * @param mixed ...$args Use \Bolt\helpers\Auth to generate appropiate array
     * @return array
     * @throws Exception
     */
    public function init(...$args): array
    {
        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $userAgent = $args[0]['user_agent'];
        unset($args[0]['user_agent']);

        $this->write($this->packer->pack(0x01, $userAgent, $args[0]));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            // ..but must immediately close the connection after the failure has been sent.
            $this->connection->disconnect();
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $output;
    }

    /**
     * Send RUN message
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---run
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function run(...$args): array
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

        if ($signature == self::IGNORED) {
            throw new MessageException('RUN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $output;
    }

    /**
     * Send PULL_ALL message
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---pull_all
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function pullAll(...$args): array
    {
        $this->write($this->packer->pack(0x3F));

        $output = [];
        do {
            $message = $this->read($signature);
            $output[] = $message;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $this->ackFailure();
            $last = array_pop($output);
            throw new MessageException($last['message'] . ' (' . $last['code'] . ')');
        }

        if ($signature == self::IGNORED) {
            throw new MessageException('PULL_ALL message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $output;
    }

    /**
     * Send DISCARD_ALL message
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---discard_all
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function discardAll(...$args): array
    {
        $this->write($this->packer->pack(0x2F));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->ackFailure();
            throw new MessageException($message['message'] . ' (' . $message['code'] . ')');
        }

        if ($signature == self::IGNORED) {
            throw new MessageException('DISCARD_ALL message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }

    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---ack_failure
     * @throws Exception
     */
    private function ackFailure()
    {
        $this->write($this->packer->pack(0x0E));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            throw new MessageException($message['message'] . ' (' . $message['code'] . ')');
        }
    }

    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT had just successfully completed.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---reset
     * @return void No need to return anything because on error it throws Exception
     * @throws Exception
     */
    public function reset()
    {
        $this->write($this->packer->pack(0x0F));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            throw new MessageException($message['message'] . ' (' . $message['code'] . ')');
        }
    }

}

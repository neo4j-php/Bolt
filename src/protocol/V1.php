<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\error\PackException;
use Exception;

/**
 * Class Protocol version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
class V1 extends AProtocol
{

    /**
     * Send INIT message
     * The INIT message is a request for the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-init
     * @param mixed ...$args Use \Bolt\helpers\Auth to generate appropiate array
     * @return array
     * @throws Exception
     */
    public function init(...$args): array
    {
        $this->serverState->is(ServerState::CONNECTED);

        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $userAgent = $args[0]['user_agent'];
        unset($args[0]['user_agent']);

        $this->write($this->packer->pack(0x01, $userAgent, $args[0]));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            // ..but must immediately close the connection after the failure has been sent.
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }

    /**
     * Send RUN message
     * A RUN message submits a new query for execution, the result of which will be consumed by a subsequent message, such as PULL_ALL.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function run(...$args): array
    {
        $this->serverState->is(ServerState::READY);

        if (empty($args)) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x10, $args[0], (object)($args[1] ?? [])));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set(ServerState::STREAMING);
        return $message;
    }

    /**
     * Send PULL_ALL message
     * The PULL_ALL message issues a request to stream the outstanding result back to the client, before returning to a READY state.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param mixed ...$args
     * @return array Array of records with last success entry
     * @throws Exception
     */
    public function pullAll(...$args): array
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        $this->write($this->packer->pack(0x3F));

        $output = [];
        do {
            $message = $this->read($signature);
            $output[] = $message;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set($this->serverState->get() === ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
        return $output;
    }

    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function discardAll(...$args): array
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        $this->write($this->packer->pack(0x2F));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set($this->serverState->get() === ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
        return $message;
    }

    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-ack-failure
     * @throws Exception
     */
    public function ackFailure()
    {
        $this->write($this->packer->pack(0x0E));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
    }

    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT had just successfully completed.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-reset
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function reset(): array
    {
        $this->write($this->packer->pack(0x0F));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }
}

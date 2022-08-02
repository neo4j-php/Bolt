<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\error\PackException;
use Exception;

/**
 * Class Protocol version 3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-3
 * @package Bolt\protocol
 */
class V3 extends V2
{

    /**
     * @inheritDoc
     * @deprecated Replaced with HELLO message
     */
    public function init(...$args): array
    {
        return $this->hello(...$args);
    }

    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param mixed ...$args Use \Bolt\helpers\Auth to generate appropiate array
     * @return array
     * @throws Exception
     */
    public function hello(...$args): array
    {
        $this->serverState->is(ServerState::CONNECTED);

        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x01, $args[0]));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }

    /**
     * Send RUN message
     * The RUN message requests that a Cypher query is executed with a set of parameters and additional extra data.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @param string|array ...$args query, parameters, extra
     * @return array
     * @throws Exception
     */
    public function run(...$args): array
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY);

        if (empty($args)) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(
            0x10,
            $args[0],
            (object)($args[1] ?? []),
            (object)($args[2] ?? [])
        ));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set($this->serverState->get() === ServerState::READY ? ServerState::STREAMING : ServerState::TX_STREAMING);
        return $message;
    }

    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-begin
     * @param mixed ...$args extra
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function begin(...$args): array
    {
        $this->serverState->is(ServerState::READY);

        $this->write($this->packer->pack(0x11, (object)($args[0] ?? [])));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set(ServerState::TX_READY);
        return $message;
    }

    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-commit
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function commit(): array
    {
        $this->serverState->is(ServerState::TX_READY);

        $this->write($this->packer->pack(0x12));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }

    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-rollback
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function rollback(): array
    {
        $this->serverState->is(ServerState::TX_READY);

        $this->write($this->packer->pack(0x13));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }

    /**
     * Send GOODBYE message
     * The GOODBYE message notifies the server that the connection is terminating gracefully.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-goodbye
     * @throws Exception
     */
    public function goodbye()
    {
        $this->write($this->packer->pack(0x02));
        $this->connection->disconnect();
        $this->serverState->set(ServerState::DEFUNCT);
    }
}

<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\helpers\ServerState;
use Exception;
use Bolt\error\MessageException;

/**
 * Class Protocol version 4
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-40
 * @package Bolt\protocol
 */
class V4 extends V3
{

    /**
     * @inheritDoc
     * @deprecated Renamed to PULL
     */
    public function pullAll(...$args): array
    {
        return $this->pull(...$args);
    }

    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param array ...$args
     * @return array
     * @throws Exception
     */
    public function pull(...$args): array
    {
        $this->_pull(...$args);
        array_pop($this->pipelinedMessages);

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

        if ($message['has_more'] ?? false) {
            $this->serverState->set($this->serverState->is(ServerState::READY) ? ServerState::STREAMING : ServerState::TX_STREAMING);
        }

        return $output;
    }

    /**
     * Pipelined version of PULL
     */
    public function _pull(...$args)
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        if (count($args) == 0)
            $args[0] = ['n' => -1];
        elseif (!array_key_exists('n', $args[0]))
            $args[0]['n'] = -1;

        $this->write($this->packer->pack(0x3F, $args[0]));

        $this->pipelinedMessages[] = 'pull';
        $this->serverState->set($this->serverState->is(ServerState::STREAMING) ? ServerState::READY : ServerState::TX_READY);
    }

    /**
     * @inheritDoc
     * @deprecated Renamed to DISCARD
     */
    public function discardAll(...$args): array
    {
        return $this->discard(...$args);
    }

    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function discard(...$args): array
    {
        $this->_discard(...$args);
        array_pop($this->pipelinedMessages);

        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        if ($message['has_more'] ?? false) {
            $this->serverState->set($this->serverState->is(ServerState::READY) ? ServerState::STREAMING : ServerState::TX_STREAMING);
        }

        return $message;
    }

    /**
     * Pipelined version of DISCARD
     */
    public function _discard(...$args)
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        if (count($args) == 0)
            $args[0] = ['n' => -1];
        elseif (!array_key_exists('n', $args[0]))
            $args[0]['n'] = -1;

        $this->write($this->packer->pack(0x2F, $args[0]));

        $this->pipelinedMessages[] = 'discard';
        $this->serverState->set($this->serverState->is(ServerState::STREAMING) ? ServerState::READY : ServerState::TX_READY);
    }
}

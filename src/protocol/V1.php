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
        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $userAgent = $args[0]['user_agent'];
        unset($args[0]['user_agent']);

        return $this->io(Signatures::INIT, $userAgent, $args[0]);
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
        if (empty($args)) {
            throw new PackException('Wrong arguments count');
        }

        try {
            return $this->io(Signatures::RUN, $args[0], (object)($args[1] ?? []));
        } catch (MessageException $e) {
            $this->ackFailure(); // acknowledge the failure for backwards compatibility
            throw $e;
        }
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
        $this->write($this->packer->pack(Signatures::PULL_ALL));

        $output = [];
        do {
            $last = $this->read($signature);
            $output[] = $last;
        } while ($signature === self::RECORD);

        try {
            $this->interpretResult(Signatures::PULL_ALL, $signature, $last);
        } catch (MessageException $e) {
            $this->ackFailure();
            throw $e;
        }

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
        try {
            return $this->io(Signatures::DISCARD_ALL);
        } catch (MessageException $e) {
            $this->ackFailure(); // acknowledge the failure for backwards compatibility
            throw $e;
        }
    }

    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-ack-failure
     * @throws Exception
     */
    private function ackFailure(): void
    {
        $this->io(Signatures::ACK_FAILURE);
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
        return $this->io(Signatures::RESET);
    }
}

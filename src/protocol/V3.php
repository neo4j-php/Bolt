<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Exception;

/**
 * Class Protocol version 3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-3.html
 * @package Bolt\protocol
 */
class V3 extends V2
{

    /**
     * @inheritDoc
     * @deprecated Replaced with HELLO message
     */
    public function init(string $userAgent, array $authToken): array
    {
        $authToken['user_agent'] = $userAgent;
        return $this->hello($authToken);
    }

    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropiate array
     * @return array
     * @throws Exception
     */
    public function hello(array $extra): array
    {
        $this->write($this->packer->pack(0x01, $extra));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            throw new MessageException($message['message'], $message['code']);
        }

        return $message;
    }

    /**
     * Send RUN message
     * The RUN message requests that a Cypher query is executed with a set of parameters and additional extra data.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---run
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---run
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---run---44
     * @param string $query
     * @param array $parameters
     * @param array $extra
     * @return array
     * @throws Exception
     */
    public function run(string $query, array $parameters = [], array $extra = []): array
    {
        $this->write($this->packer->pack(
            0x10,
            $query,
            (object)$parameters,
            (object)$extra
        ));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('RUN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }

    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---begin
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---begin
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---begin---44
     * @param array $extra
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function begin(array $extra = []): array
    {
        $this->write($this->packer->pack(0x11, (object)$extra));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('BEGIN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }

    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---commit
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function commit(): array
    {
        $this->write($this->packer->pack(0x12));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('COMMIT message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }

    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---rollback
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function rollback(): array
    {
        $this->write($this->packer->pack(0x13));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('ROLLBACK message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }

    /**
     * Send GOODBYE message
     * The GOODBYE message notifies the server that the connection is terminating gracefully.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---goodbye
     * @throws Exception
     */
    public function goodbye()
    {
        $this->write($this->packer->pack(0x02));
        $this->connection->disconnect();
    }

}

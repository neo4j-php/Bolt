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
        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        return $this->io(Signatures::HELLO, $args[0]);
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
        if (empty($args)) {
            throw new PackException('Wrong arguments count');
        }

        return $this->io(Signatures::RUN, $args[0], (object)($args[1] ?? []), (object)($args[2] ?? []));
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
        return $this->io(Signatures::BEGIN, (object)($args[0] ?? []));
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
        return $this->io(Signatures::COMMIT);
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
        return $this->io(Signatures::ROLLBACK);
    }

    /**
     * Send GOODBYE message
     * The GOODBYE message notifies the server that the connection is terminating gracefully.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-goodbye
     * @throws Exception
     */
    public function goodbye(): void
    {
        $this->write($this->packer->pack(Signatures::GOODBYE));
        $this->connection->disconnect();
    }
}

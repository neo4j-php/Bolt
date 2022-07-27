<?php

namespace Bolt\helpers;

use Bolt\error\ServerStateException;

/**
 * Class ServerState
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/server-state/
 */
class ServerState
{
    /**
     * No socket connection has yet been established. This is the initial state and exists only in a logical sense prior to the socket being opened.
     */
    public const DISCONNECTED = 'DISCONNECTED';

    /**
     * After a new protocol connection has been established and handshake has been completed successfully, the server enters the CONNECTED state.
     * The connection has not yet been authenticated and permits only one transition, through successful initialization, into the READY state.
     */
    public const CONNECTED = 'CONNECTED';

    /**
     * This is not strictly a connection state, but is instead a logical state that exists after a connection has been closed. When DEFUNCT, a connection is permanently not usable.
     * This may arise due to a graceful shutdown or can occur after an unrecoverable error or protocol violation.
     * Clients and servers should clear up any resources associated with a connection on entering this state, including closing any open sockets.
     * This is a terminal state on which no further transitions may be carried out. The <DISCONNECT> signal will set the connection in the DEFUNCT server state.
     */
    public const DEFUNCT = 'DEFUNCT';

    /**
     * The READY state can handle the request messages RUN and BEGIN and receive a query.
     */
    public const READY = 'READY';

    /**
     * When STREAMING, a result is available for streaming from server to client.
     * This result must be fully consumed or discarded by a client before the server can re-enter the READY state and allow any further queries to be executed.
     */
    public const STREAMING = 'STREAMING';

    public const TX_READY = 'TX_READY';

    /**
     * When TX_STREAMING, a result is available for streaming from server to client. This result must be fully consumed or discarded by a client before the server can transition to the TX_READY state.
     */
    public const TX_STREAMING = 'TX_STREAMING';

    /**
     * When FAILED, a connection is in a temporarily unusable state. This is generally as the result of encountering a recoverable error.
     * This mode ensures that only one failure can exist at a time, preventing cascading issues from batches of work.
     */
    public const FAILED = 'FAILED';

    /**
     * This state occurs between the server receiving the jump-ahead <INTERRUPT> and the queued RESET message, (the RESET message triggers an <INTERRUPT>).
     * Most incoming messages are ignored when the server are in an INTERRUPTED state, with the exception of the RESET that allows transition back to READY.
     * The <INTERRUPT> signal will set the connection in the INTERRUPTED server state.
     */
    public const INTERRUPTED = 'INTERRUPTED';

    /**
     * Internal pointer for current server state
     * @var string
     */
    private static string $current;

    /**
     * Internal enum to verify valid server state
     * @var string[]
     */
    private static array $lt = [
        self::DISCONNECTED,
        self::CONNECTED,
        self::DEFUNCT,
        self::READY,
        self::STREAMING,
        self::TX_READY,
        self::TX_STREAMING,
        self::FAILED,
        self::INTERRUPTED
    ];

    /**
     * Get current server state
     * @return string
     */
    public static function get(): string
    {
        return self::$current;
    }

    /**
     * Set current server state
     * @param string $state
     */
    public static function set(string $state)
    {
        if (in_array($state, self::$lt))
            self::$current = $state;
    }

    /**
     * Check if current server state equals one of requested
     * @param string ...$states
     * @throws ServerStateException
     */
    public static function is(string ...$states)
    {
        foreach ($states as $state) {
            if (in_array($state, self::$lt) && self::$current === $state)
                return;
        }

        throw ServerStateException::expected(...$states);
    }
}

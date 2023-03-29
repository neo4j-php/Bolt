<?php


namespace Bolt\connection;

use Bolt\Bolt;
use Bolt\error\ConnectException;
use Bolt\error\ConnectionTimeoutException;

/**
 * Stream socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
class StreamSocket extends AConnection
{
    public function connect(): bool
    {
        $this->stream = @stream_socket_client(
            'tcp://' . $this->ip . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT | ($this->keepAlive ? STREAM_CLIENT_PERSISTENT : 0),
            stream_context_create([
                'socket' => [
                    'tcp_nodelay' => true,
                ],
                'ssl' => $this->sslContextOptions
            ])
        );

        if ($this->stream === false) {
            throw new ConnectException($errstr, $errno);
        }

        if (!stream_set_blocking($this->stream, true)) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }

        $this->configureTimeout();

        $this->configureCrypto();

        return true;
    }
}

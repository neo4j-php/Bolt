<?php

namespace Bolt\connection;

use Bolt\Bolt;
use Bolt\error\ConnectException;
use Bolt\error\ConnectionTimeoutException;

/**
 * Socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
class Socket extends AConnection
{
    public function connect(): bool
    {
        if (!extension_loaded('sockets')) {
            throw new ConnectException('PHP Extension sockets not enabled');
        }

        if ($this->keepAlive) {
            $socket = @pfsockopen($this->ip, $this->port, $errno, $errstr, $this->timeout);
            if ($socket === false) {
                throw new ConnectException($errstr, $errno);
            }
            $socket = socket_import_stream($socket);
            $this->configureSocket($socket);
        } else {
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $this->configureSocket($socket);
            $conn = @socket_connect($socket, $this->ip, $this->port);
            if (!$conn) {
                $code = socket_last_error($socket);
                throw new ConnectException(socket_strerror($code), $code);
            }
        }

        $this->stream = socket_export_stream($socket);
        stream_context_set_params($this->stream, ['ssl' => $this->sslContextOptions ]);

        $this->configureTimeout();
        $this->configureCrypto();

        return true;
    }

//    protected function configureTimeout(): void
//    {
//        if ($this->socket === false)
//            return;
//
//        $timeoutSeconds = floor($this->timeout);
//        $microSeconds = floor(($this->timeout - $timeoutSeconds) * 1000000);
//        $timeoutOption = ['sec' => $timeoutSeconds, 'usec' => $microSeconds];
//        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $timeoutOption);
//        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $timeoutOption);
//    }

    private function configureSocket(\Socket $socket): void
    {
        if (socket_set_block($socket) === false) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }

        socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
//        socket_set_option($socket, SOL_SOCKET, SO_LINGER, 1);
//        socket_set_option($socket, SOL_SOCKET, SO_BINDTODEVICE, 1);
//        socket_set_option($socket, SOL_SOCKET, SO_TYPE, 1);
        $this->configureTimeout();
    }
}

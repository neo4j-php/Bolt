<?php

namespace Bolt\connection;

use Bolt\Bolt;
use Bolt\error\ConnectException;

/**
 * Socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\connection
 */
class Socket extends AConnection
{

    /**
     * @var resource|object|bool
     */
    private $socket = false;

    /**
     * Create socket connection
     * @return bool
     * @throws ConnectException
     */
    public function connect(): bool
    {
        if (!extension_loaded('sockets')) {
            throw new ConnectException('PHP Extension sockets not enabled');
        }

        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new ConnectException('Cannot create socket');
        }

        if (socket_set_block($this->socket) === false) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }

        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        $timeoutSeconds = floor($this->timeout);
        $microSeconds = floor(($this->timeout - $timeoutSeconds) * 1000000);
        $timeoutOption = ['sec' => $timeoutSeconds, 'usec' => $microSeconds];
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $timeoutOption);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $timeoutOption);

        $conn = @socket_connect($this->socket, $this->ip, $this->port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            throw new ConnectException(socket_strerror($code), $code);
        }

        return true;
    }

    /**
     * Write buffer to socket
     * @param string $buffer
     * @throws ConnectException
     */
    public function write(string $buffer)
    {
        if ($this->socket === false) {
            throw new ConnectException('Not initialized socket');
        }

        $size = mb_strlen($buffer, '8bit');
        $sent = 0;

        if (Bolt::$debug)
            $this->printHex($buffer);

        while (0 < $size) {
            $sent = socket_write($this->socket, $buffer, $size);
            if ($sent === false) {
                $code = socket_last_error($this->socket);
                throw new ConnectException(socket_strerror($code), $code);
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    /**
     * Read buffer from socket
     * @param int $length
     * @return string
     * @throws ConnectException
     */
    public function read(int $length = 2048): string
    {
        $output = '';

        if ($this->socket === false) {
            throw new ConnectException('Not initialized socket');
        }

        do {
            $readed = socket_read($this->socket, $length - mb_strlen($output, '8bit'), PHP_BINARY_READ);
            if ($readed === false) {
                $code = socket_last_error($this->socket);
                throw new ConnectException(socket_strerror($code), $code);
            }
            $output .= $readed;
        } while (mb_strlen($output, '8bit') < $length);

        if (Bolt::$debug)
            $this->printHex($output, false);

        return $output;
    }

    /**
     * Close socket connection
     */
    public function disconnect()
    {
        if ($this->socket !== false) {
            @socket_shutdown($this->socket);
            @socket_close($this->socket);
        }
    }
}

<?php

namespace Bolt;

/**
 * Socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt
 */
final class Socket
{

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @param string $ip
     * @param int $port
     * @param int $timeout
     * @throws \Exception
     */
    public function __construct(string $ip, int $port, int $timeout)
    {
        if (!extension_loaded('sockets')) {
            Bolt::error('PHP Extension sockets not enabled');
        }

        $this->ip = $ip;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Create socket connection
     * @return bool
     * @throws \Exception
     */
    public function connect(): bool
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!is_resource($this->socket)) {
            Bolt::error('Cannot create socket');
            return false;
        }

        if (socket_set_block($this->socket) === false) {
            Bolt::error('Cannot set socket into blocking mode');
            return false;
        }

        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $this->timeout, 'usec' => 0]);

        $conn = @socket_connect($this->socket, $this->ip, $this->port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            Bolt::error(socket_strerror($code), $code);
            return false;
        }

        return true;
    }

    /**
     * Write buffer to socket
     * @param string $buffer
     * @throws \Exception
     */
    public function write(string $buffer)
    {
        if (!is_resource($this->socket)) {
            Bolt::error('Not initialized socket');
            return;
        }

        $size = mb_strlen($buffer, '8bit');
        $sent = 0;

        if (Bolt::$debug)
            $this->printHex($buffer);

        while ($sent < $size) {
            $sent = socket_write($this->socket, $buffer, $size);
            if ($sent === false) {
                $code = socket_last_error($this->socket);
                Bolt::error(socket_strerror($code), $code);
                return;
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    /**
     * Read buffer from socket
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public function read(int $length = 2048): string
    {
        $output = '';

        if (!is_resource($this->socket)) {
            Bolt::error('Not initialized socket');
            return $output;
        }

        do {
            $readed = socket_read($this->socket, $length - mb_strlen($output, '8bit'), PHP_BINARY_READ);
            if ($readed === false) {
                $code = socket_last_error($this->socket);
                Bolt::error(socket_strerror($code), $code);
            } else {
                $output .= $readed;
            }
        } while (mb_strlen($output, '8bit') < $length);

        if (Bolt::$debug)
            $this->printHex($output, false);

        return $output;
    }

    /**
     * Print buffer as HEX
     * @param string $str
     * @param bool $write
     */
    private function printHex(string $str, bool $write = true)
    {
        $str = implode(unpack('H*', $str));
        echo '<pre>';
        echo $write ? '> ' : '< ';
        foreach (str_split($str, 8) as $chunk) {
            echo implode(' ', str_split($chunk, 2));
            echo '    ';
        }
        echo '</pre>';
    }

    /**
     * Close socket connection
     */
    public function disconnect()
    {
        socket_shutdown($this->socket);
        @socket_close($this->socket);
    }

}

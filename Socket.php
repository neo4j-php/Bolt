<?php

namespace Bolt;

use Bolt\PackStream\IUnpacker;
use Exception;

/**
 * Static class Socket
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt
 */
final class Socket
{
    /**
     * @var resource
     */
    private $socket;

    /**
     * @param string $ip
     * @param int $port
     * @param int $timeout
     * @throws Exception
     */
    public function __construct(string $ip, int $port, int $timeout)
    {
        if (!extension_loaded('sockets')) {
            Bolt::error('PHP Extension sockets not enabled');
            return;
        }

        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!is_resource($this->socket)) {
            Bolt::error('Cannot create socket');
            return;
        }

        if (socket_set_block($this->socket) === false) {
            Bolt::error('Cannot set socket into blocking mode');
            return;
        }

        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $timeout, 'usec' => 0]);

        $conn = socket_connect($this->socket, $ip, $port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            Bolt::error(socket_strerror($code), $code);
        }
    }

    /**
     * Write to socket
     * @param string $buffer
     * @throws Exception
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
            Bolt::printHex($buffer);

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
     * Read unpacked from socket
     * @param IUnpacker $unpacker
     * @return mixed
     * @throws Exception
     */
    public function read(IUnpacker $unpacker)
    {
        if (!is_resource($this->socket)) {
            Bolt::error('Not initialized socket');
            return;
        }

        $msg = '';
        while (true) {
            $header = $this->readBuffer(2);
            if (ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->readBuffer($length);
        }

        $output = null;
        $signature = 0;
        if (!empty($msg)) {
            if (Bolt::$debug)
                Bolt::printHex($msg, false);

            try {
                $output = $unpacker->unpack($msg, $signature);
            } catch (Exception $ex) {
                Bolt::error($ex->getMessage());
            }
        }

        return [$signature, $output];
    }

    /**
     * Read buffer from socket
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function readBuffer(int $length = 2048): string
    {
        if (!is_resource($this->socket)) {
            Bolt::error('Not initialized socket');
            return;
        }

        $output = '';
        do {
            $readed = socket_read($this->socket, $length - mb_strlen($output, '8bit'), PHP_BINARY_READ);
            if ($readed === false) {
                $code = socket_last_error($this->socket);
                Bolt::error(socket_strerror($code), $code);
            } else {
                $output .= $readed;
            }
        } while (mb_strlen($output, '8bit') < $length);
        return $output;
    }

    /**
     * Close socket
     */
    public function __destruct()
    {
        @socket_close($this->socket);
    }
}

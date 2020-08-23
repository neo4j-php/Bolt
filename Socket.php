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
    public static $socket;

    /**
     * @param string $ip
     * @param int $port
     * @param int $timeout
     * @throws Exception
     */
    public static function initialize(string $ip, int $port, int $timeout)
    {
        if (is_resource(self::$socket))
            return;

        if (!extension_loaded('sockets')) {
            Bolt::error('PHP Extension sockets not enabled');
            return;
        }

        self::$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!is_resource(self::$socket)) {
            Bolt::error('Cannot create socket');
            return;
        }

        socket_set_block(Socket::$socket);
        socket_set_option(Socket::$socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option(Socket::$socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        socket_set_option(Socket::$socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
        socket_set_option(Socket::$socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $timeout, 'usec' => 0]);

        $conn = socket_connect(self::$socket, $ip, $port);
        if (!$conn) {
            $code = socket_last_error(Socket::$socket);
            Bolt::error(socket_strerror($code), $code);
            return;
        }
    }

    /**
     * Write to socket
     * @param string $buffer
     * @throws Exception
     */
    public static function write(string $buffer)
    {
        if (!is_resource(self::$socket)) {
            Bolt::error('Not initialized socket');
            return;
        }

        $size = mb_strlen($buffer, '8bit');
        $sent = 0;

        if (Bolt::$debug)
            Bolt::printHex($buffer);

        while ($sent < $size) {
            $sent = socket_write(self::$socket, $buffer, $size);
            if ($sent === false) {
                $code = socket_last_error(self::$socket);
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
    public static function read(IUnpacker $unpacker)
    {
        if (!is_resource(self::$socket)) {
            Bolt::error('Not initialized socket');
            return;
        }

        $msg = '';
        while (true) {
            $header = self::readBuffer(2);
            if (ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= self::readBuffer($length);
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
     */
    public static function readBuffer(int $length = 2048): string
    {
        $output = '';
        do {
            $output .= socket_read(self::$socket, $length - mb_strlen($output, '8bit'), PHP_BINARY_READ);
        } while (mb_strlen($output, '8bit') < $length);
        return $output;
    }
}

<?php


namespace Bolt\connection;

use Bolt\Bolt;
use Exception;

/**
 * Stream socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\connection
 */
class StreamSocket extends AConnection
{

    /**
     * @var array
     */
    private $sslContextOptions = [];

    /**
     * @var resource
     */
    private $stream;

    /**
     * Set SSL Context options
     * @link https://www.php.net/manual/en/context.ssl.php
     * @param array $options
     */
    public function setSslContextOptions(array $options)
    {
        $this->sslContextOptions = $options;
    }

    /**
     * Connect
     * @return bool
     * @throws Exception
     */
    public function connect(): bool
    {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ],
            'ssl' => $this->sslContextOptions
        ]);

        $this->stream = stream_socket_client( 'tcp://' . $this->ip . ':' . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);

        if ($this->stream === false) {
            Bolt::error($errstr . ' (' . $errno . ')');
            return false;
        }

        if (!stream_set_blocking($this->stream, true)) {
            Bolt::error('Cannot set socket into blocking mode');
            return false;
        }

        if (!empty($this->sslContextOptions)) {
            if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) !== true) {
                Bolt::error('Enable encryption error');
                return false;
            }
        }

        return true;
    }

    /**
     * Write to connection
     * @param string $buffer
     * @throws Exception
     */
    public function write(string $buffer)
    {
        if (Bolt::$debug)
            $this->printHex($buffer);


        if (fwrite($this->stream, $buffer) === false)
            Bolt::error('Write error');
    }

    /**
     * Read from connection
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function read(int $length = 2048): string
    {
        $res = fread($this->stream, $length);
        if (empty($res))
            Bolt::error('Read error');

        if (Bolt::$debug)
            $this->printHex($res, false);

        return (string)$res;
    }

    /**
     * Close connection
     */
    public function disconnect()
    {
        if (is_resource($this->stream))
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
    }

}

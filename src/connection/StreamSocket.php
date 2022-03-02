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
     * ConnectException
     * @return bool
     * @throws ConnectException
     */
    public function connect(): bool
    {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ],
            'ssl' => $this->sslContextOptions
        ]);

        $this->stream = @stream_socket_client( 'tcp://' . $this->ip . ':' . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);

        if ($this->stream === false) {
            throw new ConnectException($errstr, $errno);
        }

        if (!stream_set_blocking($this->stream, true)) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }

        if (!empty($this->sslContextOptions)) {
            if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) !== true) {
                throw new ConnectException('Enable encryption error');
            }
        }

        $this->configureTimeout();

        return true;
    }

    /**
     * Write to connection
     * @param string $buffer
     * @throws ConnectException
     */
    public function write(string $buffer)
    {
        if (Bolt::$debug)
            $this->printHex($buffer);


        if (fwrite($this->stream, $buffer) === false)
            throw new ConnectException('Write error');
    }

    /**
     * Read from connection
     * @param int $length
     * @return string
     * @throws ConnectException
     */
    public function read(int $length = 2048): string
    {
        $res = stream_get_contents($this->stream, $length);

        if (stream_get_meta_data($this->stream)["timed_out"])
            throw ConnectionTimeoutException::createFromTimeout($this->timeout);

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

    public function setTimeout(float $timeout): void
    {
        parent::setTimeout($timeout);
        $this->configureTimeout();
    }

    /**
     * @return void
     * @throws ConnectException
     */
    private function configureTimeout(): void
    {
        $timeout = (int)floor($this->timeout);
        if (!stream_set_timeout($this->stream, $timeout, (int)floor(($this->timeout - $timeout) * 1000000))) {
            throw new ConnectException('Cannot set timeout on stream');
        }
    }
}

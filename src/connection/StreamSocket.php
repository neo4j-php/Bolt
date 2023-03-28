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
    private array $sslContextOptions = [];

    /**
     * @var resource
     */
    private $stream;

    /**
     * Set SSL Context options
     * @link https://www.php.net/manual/en/context.ssl.php
     */
    public function setSslContextOptions(array $options): void
    {
        $this->sslContextOptions = $options;
    }

    public function connect(): bool
    {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ],
            'ssl' => $this->sslContextOptions
        ]);

        $this->stream = @stream_socket_client(
            'tcp://' . $this->ip . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT | ($this->keepAlive ? STREAM_CLIENT_PERSISTENT : 0),
            $context
        );

        if ($this->stream === false) {
            throw new ConnectException($errstr, $errno);
        }

        if (!stream_set_blocking($this->stream, true)) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }

        $this->configureTimeout();

        // If the connection is being reused we do not need to re-configure SSL.
        // Re-configuring it is actually impossible and results in a crash.
        if ($this->tell() > 0) {
            return true;
        }

        if (!empty($this->sslContextOptions)) {
            if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) !== true) {
                throw new ConnectException('Enable encryption error');
            }
        }

        return true;
    }

    public function write(string $buffer): void
    {
        if (Bolt::$debug)
            $this->printHex($buffer);

        $size = mb_strlen($buffer, '8bit');

        $time = microtime(true);
        while (0 < $size) {
            $sent = fwrite($this->stream, $buffer);

            if ($sent === false) {
                if (microtime(true) - $time >= $this->timeout)
                    throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
                else
                    throw new ConnectException('Write error');
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    public function read(int $length = 2048): string
    {
        $output = '';
        do {
            $readed = stream_get_contents($this->stream, $length - mb_strlen($output, '8bit'));

            if (stream_get_meta_data($this->stream)['timed_out'] ?? false)
                throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
            if ($readed === false)
                throw new ConnectException('Read error');

            $output .= $readed;
        } while (mb_strlen($output, '8bit') < $length);

        if (Bolt::$debug)
            $this->printHex($output, 'S: ');

        return $output;
    }

    public function disconnect(): void
    {
        if (is_resource($this->stream))
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
    }

    /**
     * @throws ConnectException
     */
    public function setTimeout(float $timeout): void
    {
        parent::setTimeout($timeout);
        $this->configureTimeout();
    }

    /**
     * @throws ConnectException
     */
    private function configureTimeout(): void
    {
        if (is_resource($this->stream)) {
            $timeout = (int)floor($this->timeout);
            if (!stream_set_timeout($this->stream, $timeout, (int)floor(($this->timeout - $timeout) * 1000000))) {
                throw new ConnectException('Cannot set timeout on stream');
            }
        }
    }

    public function tell(): bool|int
    {
        if ($this->stream) {
            return ftell($this->stream);
        }

        return false;
    }

    public function getId(): string|false
    {
        if ($this->stream) {
            return md5(json_encode([
                get_resource_id($this->stream),
                $this->ip,
                $this->port,
                $this->keepAlive
            ]));
        }

        return false;
    }
}

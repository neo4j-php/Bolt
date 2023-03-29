<?php

namespace Bolt\connection;

use Bolt\Bolt;
use Bolt\error\ConnectException;
use Bolt\error\ConnectionTimeoutException;

/**
 * Class AConnection
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
abstract class AConnection implements IConnection
{
    protected bool $keepAlive = false;
    protected array $sslContextOptions = [];

    /**
     * @var resource|false
     */
    protected $stream;

    public function __construct(
        protected string $ip = '127.0.0.1',
        protected int    $port = 7687,
        protected float  $timeout = 15
    )
    {
        if (filter_var($this->ip, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($this->ip, PHP_URL_SCHEME);
            if (!empty($scheme)) {
                $this->ip = str_replace($scheme . '://', '', $this->ip);
            }
        }
    }

    /**
     * Print buffer as HEX
     */
    protected function printHex(string $str, string $prefix = 'C: '): void
    {
        $str = implode(unpack('H*', $str));
        echo '<pre>' . $prefix;
        foreach (str_split($str, 8) as $chunk) {
            echo implode(' ', str_split($chunk, 2));
            echo '    ';
        }
        echo '</pre>' . PHP_EOL;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
        $this->configureTimeout();
    }

    public function keepAlive(bool $keepAlive = true): void
    {
        $this->keepAlive = $keepAlive;
    }

    public function isKeptAlive(): bool
    {
        return $this->keepAlive;
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

    public function tell(): bool|int
    {
        if ($this->stream) {
            return ftell($this->stream);
        }

        return false;
    }

    /**
     * Set SSL Context options
     * @link https://www.php.net/manual/en/context.ssl.php
     */
    public function setSslContextOptions(array $options): void
    {
        $this->sslContextOptions = $options;
    }

    /**
     * @return resource
     */
    protected function createStreamContext()
    {
        return stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ],
            'ssl' => $this->sslContextOptions
        ]);
    }

    /**
     * @throws ConnectException
     */
    protected function configureTimeout(): void
    {
        if (is_resource($this->stream)) {
            $timeout = (int)floor($this->timeout);
            if (!stream_set_timeout($this->stream, $timeout, (int)floor(($this->timeout - $timeout) * 1000000))) {
                throw new ConnectException('Cannot set timeout on stream');
            }
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->stream))
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
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

    /**
     * @return void
     * @throws ConnectException
     */
    protected function configureCrypto(): void
    {
        $meta = stream_get_meta_data($this->stream);
        if (!empty($this->sslContextOptions) && !array_key_exists('crypto', $meta)) {
            if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) !== true) {
                throw new ConnectException('Enable encryption error');
            }
        }
    }
}

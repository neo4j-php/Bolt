<?php


namespace Bolt\connection;

use Bolt\error\ConnectException;
use Psr\SimpleCache\CacheInterface;

/**
 * Persistent stream socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
class PStreamSocket extends StreamSocket
{
    private string|null $identifier = null;

    private CacheInterface|null $cache = null;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
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
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
            $context
        );

        if ($this->stream === false) {
            throw new ConnectException($errstr, $errno);
        }

        var_dump($this->getIdentifier());

        if (!stream_set_blocking($this->stream, false)) {
            throw new ConnectException('Cannot set socket into non-blocking mode');
        }

        if (!empty($this->sslContextOptions)) {
            if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) !== true) {
                throw new ConnectException('Enable encryption error');
            }
        }

        $this->configureTimeout();

        return true;
    }

    public function getIdentifier(): string|bool
    {
        if ($this->identifier === null)
            $this->identifier = str_replace(':', '_', stream_socket_get_name($this->stream, false)) . '_' . str_replace(':', '_', stream_socket_get_name($this->stream, true));
        return $this->identifier;
    }

    public function read(int $length = 2048): string
    {
        return $this->canRead() ? parent::read($length) : '';
    }

    private function canRead(): bool
    {
        $read = [$this->stream];
        $write = $except = null;
        return stream_select($read, $write,$except, 0, 0) === 1;
    }

    public function disconnect(): void
    {
        if (is_resource($this->stream)) {
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
            fclose($this->stream);
            unset($this->stream);
            if ($this->cache instanceof CacheInterface) {
                $this->cache->delete($this->getIdentifier());
            }
        }
    }
}

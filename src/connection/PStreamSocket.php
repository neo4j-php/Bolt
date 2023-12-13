<?php


namespace Bolt\connection;

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

    protected int $connectionFlags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function connect(): bool
    {
        $result = parent::connect();

        //dump TCP buffer leftovers
        $read = [$this->stream];
        $write = $except = null;
        if (stream_select($read, $write, $except, 0) ===  1) {
            do {
                $r = fread($this->stream, 1024);
            } while ($r !== false && mb_strlen($r) == 1024);
        }

        return $result;
    }

    public function getIdentifier(): string
    {
        if ($this->identifier === null)
            $this->identifier = str_replace(':', '_', stream_socket_get_name($this->stream, false)) . '_' . str_replace(':', '_', stream_socket_get_name($this->stream, true));
        return $this->identifier;
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

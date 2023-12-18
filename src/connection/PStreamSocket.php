<?php


namespace Bolt\connection;

use Bolt\helpers\FileCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Persistent stream socket class
 *
 * Because PHP is stateless, using this connection class requires storing meta information about active TCP connection.
 * Default storage is FileCache which you can change with `setCache`.
 *
 * If your system reuse persistent connection and meta information about it was lost for some reason,
 * your attemt to connect will end with ConnectionTimeoutException.
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
class PStreamSocket extends StreamSocket
{
    private string $identifier;
    private CacheInterface $cache;

    protected int $connectionFlags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getCache(): CacheInterface
    {
        if (empty($this->cache))
            $this->cache = new FileCache();
        return $this->cache;
    }

    public function connect(): bool
    {
        $result = parent::connect();

        //dump TCP buffer leftovers
        $read = [$this->stream];
        $write = $except = null;
        if (stream_select($read, $write, $except, 0) === 1) {
            do {
                $r = fread($this->stream, 1024);
            } while ($r !== false && mb_strlen($r) == 1024);
        }

        return $result;
    }

    public function getIdentifier(): string
    {
        if (empty($this->identifier))
            $this->identifier = str_replace(':', '_', stream_socket_get_name($this->stream, false)) . '_' . str_replace(':', '_', stream_socket_get_name($this->stream, true));
        return $this->identifier;
    }

    public function disconnect(): void
    {
        if (is_resource($this->stream)) {
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
            fclose($this->stream);
            unset($this->stream);
            $this->getCache()->delete($this->getIdentifier());
        }
    }
}

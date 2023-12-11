<?php

namespace Bolt;

use Bolt\error\ConnectException;
use Bolt\error\BoltException;
use Bolt\protocol\{AProtocol, Response, ServerState};
use Bolt\connection\IConnection;
use Bolt\helpers\FileCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Main class Bolt
 * Bolt protocol library using TCP socket connection
 * Acts as factory which provides protocol class by requested version
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt
 */
final class Bolt
{
    private array $protocolVersions = [];
    private int $packStreamVersion = 1;

    public static bool $debug = false;
    public ServerState $serverState;

    public function __construct(private IConnection $connection, private CacheInterface|null $cache = null)
    {
        $this->setProtocolVersions(5.4, 5, 4.4);

        if ($this->cache === null) {
            $this->cache = new FileCache();
        }
    }

    /**
     * Connect via Connection, execute handshake on it, create and return protocol version class
     * @throws BoltException
     */
    public function build(): AProtocol
    {
        $this->serverState = new ServerState();
        $this->serverState->is(ServerState::DISCONNECTED, ServerState::DEFUNCT);

        $protocol = null;

        try {
            if (!$this->connection->connect()) {
                throw new ConnectException('Connection failed');
            }

            if ($this->connection instanceof \Bolt\connection\PStreamSocket) {
                $protocol = $this->persistentBuild();
            }

            if (empty($protocol)) {
                $protocol = $this->normalBuild();
                $this->serverState->set(ServerState::CONNECTED);
            }
        } catch (BoltException $e) {
            $this->serverState->set(ServerState::DEFUNCT);
            $this->connection->disconnect();
            throw $e;
        }

        if ($this->connection instanceof \Bolt\connection\PStreamSocket) {
            $this->cache->set($this->connection->getIdentifier(), $protocol->getVersion());
        }

        return $protocol;
    }

    private function normalBuild(): AProtocol
    {
        $version = $this->handshake();

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $version);
        if (!class_exists($protocolClass)) {
            throw new ConnectException('Requested Protocol version (' . $version . ') not yet implemented');
        }

        return new $protocolClass($this->packStreamVersion, $this->connection, $this->serverState);
    }

    private function persistentBuild(): AProtocol|null
    {
        $this->connection->setCache($this->cache);

        $version = $this->cache->get($this->connection->getIdentifier());
        if (empty($version)) {
            return null;
        }

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $version);
        if (!class_exists($protocolClass)) {
            throw new ConnectException('Requested Protocol version (' . $version . ') not yet implemented');
        }

        /** @var AProtocol $protocol */
        $protocol = new $protocolClass($this->packStreamVersion, $this->connection, $this->serverState);

        /** @var Response $response */
        $response = $protocol->reset()->getResponse();
        if ($response->getSignature() != Response::SIGNATURE_SUCCESS) {
            $this->connection->disconnect();
            $this->connection->connect();
            return null;
        }

        return $protocol;
    }

    public function setProtocolVersions(int|float|string ...$v): Bolt
    {
        $this->protocolVersions = array_slice($v, 0, 4);
        while (count($this->protocolVersions) < 4)
            $this->protocolVersions[] = 0;
        return $this;
    }

    public function setPackStreamVersion(int $version = 1): Bolt
    {
        $this->packStreamVersion = $version;
        return $this;
    }

    /**
     * @link https://www.neo4j.com/docs/bolt/current/bolt/handshake/
     * @throws BoltException
     */
    private function handshake(): string
    {
        if (self::$debug)
            echo 'HANDSHAKE';

        $this->connection->write(chr(0x60) . chr(0x60) . chr(0xb0) . chr(0x17) . $this->packProtocolVersions());

        $bytes = $this->connection->read(4);
        if ($bytes == 'HTTP')
            throw new ConnectException('Cannot to connect to Bolt service on ' . $this->connection->getIp() . ':' . $this->connection->getPort() . ' (looks like HTTP)');
        $version = $this->unpackProtocolVersion($bytes);
        if (empty($version))
            throw new ConnectException('Wrong version');

        return $version;
    }

    /**
     * Read and compose selected protocol version
     */
    private function unpackProtocolVersion(string $bytes): ?string
    {
        $result = [];

        foreach (mb_str_split($bytes, 1, '8bit') as $ch) {
            $result[] = unpack('C', $ch)[1] ?? 0;
        }

        while (count($result) > 0 && reset($result) == 0) {
            array_shift($result);
        }

        $version = implode('.', array_reverse($result));
        return in_array($version, $this->protocolVersions) ? $version : null;
    }

    /**
     * Pack requested protocol versions
     */
    private function packProtocolVersions(): string
    {
        $versions = [];

        foreach ($this->protocolVersions as $v) {
            if (is_int($v))
                $versions[] = pack('N', $v);
            else {
                $splitted = explode('.', (string)$v);
                $splitted = array_reverse($splitted);
                while (count($splitted) < 4)
                    array_unshift($splitted, 0);
                foreach ($splitted as $s)
                    $versions[] = pack('C', $s);
            }
        }

        return implode('', $versions);
    }
}

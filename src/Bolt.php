<?php

namespace Bolt;

use Bolt\error\ConnectException;
use Bolt\error\BoltException;
use Bolt\protocol\{AProtocol, ServerState};
use Bolt\connection\IConnection;

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

    public function __construct(private IConnection $connection)
    {
        $this->setProtocolVersions(5, 4.4);
    }

    /**
     * Connect via Connection, execute handshake on it, create and return protocol version class
     * @throws BoltException
     */
    public function build(): AProtocol
    {
        $this->serverState = new ServerState();

        try {
            if (!$this->connection->connect()) {
                throw new ConnectException('Connection failed');
            }

            $metaNamespace = sys_get_temp_dir() . '/' . $this->connection->getId();
            if ($this->protocolCanBeResumed($metaNamespace)) {
                return $this->resumeProtocol($metaNamespace);
            }

            // If connection was reused but its protocol information is lost we have no choice but to reconnect
            // on a connection that is not being kept alive.
            $this->rebootConnectionIfNeeded();

            $version = $this->handshake();
            $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $version);
            $protocol = $this->createProtocol($protocolClass);


            $this->initialiseConnectedServerstate($metaNamespace);
            if ($this->connection->isKeptAlive()) {
                file_put_contents($metaNamespace . '-protocol', $protocolClass);
            }

            return $protocol;
        } catch (ConnectException $e) {
            $this->serverState->set(ServerState::DEFUNCT);

            throw $e;
        }
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

    public function setConnection(IConnection $connection): Bolt
    {
        $this->connection = $connection;
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

    private function resumeProtocol(string $metaNamespace): AProtocol
    {
        $this->serverState = new ServerState(fopen($metaNamespace .'-state', 'r+'));
        $protocolClass = file_get_contents($metaNamespace.'-protocol');

        return $this->createProtocol($protocolClass);
    }

    /**
     * @return void
     * @throws ConnectException
     */
    public function rebootConnectionIfNeeded(): void
    {
        if ($this->connection->tell() > 0) {
            $this->connection->disconnect();
            $this->connection->connect();
        }
    }

    /**
     * @param string $metaNamespace
     * @return bool
     */
    public function protocolCanBeResumed(string $metaNamespace): bool
    {
        return ($this->connection->isKeptAlive() &&
            $this->connection->tell() > 0 &&
            file_exists($metaNamespace . '-protocol') &&
            file_exists($metaNamespace . '-state')
        );
    }

    /**
     * @param string $metaNamespace
     * @return void
     */
    public function initialiseServerState(string $metaNamespace): void
    {
        if ($this->connection->isKeptAlive()) {
            $this->serverState = new ServerState(fopen($metaNamespace . '-state', 'r+'));
        } else {
            $this->serverState = new ServerState();
            $this->serverState->set(ServerState::DISCONNECTED);
        }
    }

    public function createProtocol(string $protocolClass): AProtocol
    {
        if (!class_exists($protocolClass)) {
            throw new ConnectException(sprintf(
                'Requested Protocol version (%s) not yet implemented',
                str_replace("\\Bolt\\protocol\\V", '', str_replace('_', '.', $protocolClass))
            ));
        }

        return new $protocolClass($this->packStreamVersion, $this->connection, $this->serverState);
    }

    private function initialiseConnectedServerstate(string $metaNamespace): void
    {
        if ($this->connection->isKeptAlive()) {
            file_put_contents($metaNamespace . '-state', ServerState::CONNECTED);
            $this->serverState = new ServerState(fopen($metaNamespace . '-state', 'r+'));
        } else {
            $this->serverState->set(ServerState::CONNECTED);
        }
    }
}

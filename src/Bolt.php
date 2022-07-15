<?php

namespace Bolt;

use Bolt\error\{ConnectException, PackException, UnpackException};
use Exception;
use Bolt\PackStream\{IPacker, IUnpacker};
use Bolt\protocol\AProtocol;
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
    private IPacker $packer;
    private IUnpacker $unpacker;
    private IConnection $connection;
    private array $versions = [4.4, 4.3, 4.2, 3];
    public static bool $debug = false;

    /**
     * Bolt constructor
     * @param IConnection $connection
     * @throws Exception
     */
    public function __construct(IConnection $connection)
    {
        $this->connection = $connection;
        $this->setPackStreamVersion();
    }

    /**
     * Connect via Connection, execute handshake on it, create and return protocol version class
     * @return AProtocol
     * @throws Exception
     */
    public function build(): AProtocol
    {
        if (!$this->connection->connect())
            throw new ConnectException('Connection failed');

        $version = $this->handshake();

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $version);
        if (!class_exists($protocolClass))
            throw new ConnectException('Requested Protocol version (' . $version . ') not yet implemented');

        return new $protocolClass($this->packer, $this->unpacker, $this->connection);
    }

    /**
     * @param int|float|string ...$v
     * @return Bolt
     */
    public function setProtocolVersions(...$v): Bolt
    {
        $this->versions = array_slice($v, 0, 4);
        while (count($this->versions) < 4)
            $this->versions[] = 0;
        return $this;
    }

    /**
     * @param int $version
     * @return Bolt
     * @throws Exception
     */
    public function setPackStreamVersion(int $version = 1): Bolt
    {
        $packerClass = "\\Bolt\\PackStream\\v" . $version . "\\Packer";
        if (!class_exists($packerClass)) {
            throw new PackException('Requested PackStream version (' . $version . ') not yet implemented');
        }
        $this->packer = new $packerClass();

        $unpackerClass = "\\Bolt\\PackStream\\v" . $version . "\\Unpacker";
        if (!class_exists($unpackerClass)) {
            throw new UnpackException('Requested PackStream version (' . $version . ') not yet implemented');
        }
        $this->unpacker = new $unpackerClass();

        return $this;
    }

    /**
     * @link https://www.neo4j.com/docs/bolt/current/bolt/handshake/
     * @return string
     * @throws Exception
     */
    private function handshake(): string
    {
        if (self::$debug)
            echo 'HANDSHAKE';

        $this->connection->write(chr(0x60) . chr(0x60) . chr(0xb0) . chr(0x17));
        $this->connection->write($this->packProtocolVersions());

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
     * @param string $bytes
     * @return string|null
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
        return in_array($version, $this->versions) ? $version : null;
    }

    /**
     * Pack requested protocol versions
     * @return string
     */
    private function packProtocolVersions(): string
    {
        $versions = [];

        foreach ($this->versions as $v) {
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

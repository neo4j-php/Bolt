<?php

namespace Bolt;

use Bolt\error\{
    ConnectException,
    PackException,
    UnpackException
};
use Exception;
use Bolt\PackStream\{IPacker, IUnpacker};
use Bolt\protocol\AProtocol;
use Bolt\connection\IConnection;

/**
 * Main class Bolt
 * Bolt protocol library using TCP socket connection
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 */
final class Bolt
{

    /**
     * @var IPacker
     */
    private $packer;

    /**
     * @var IUnpacker
     */
    private $unpacker;

    /**
     * @var AProtocol
     */
    private $protocol;

    /**
     * @var IConnection
     */
    private $connection;

    /**
     * @var array
     */
    private $versions = [4.3, 4.1, 4, 3];

    /**
     * @var float
     */
    private $version;

    /**
     * @var array|null
     */
    private $routing = null;

    /**
     * Print debug info
     * @var bool
     */
    public static $debug = false;

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
     * @param int|float|string ...$v
     * @return Bolt
     */
    public function setProtocolVersions(...$v): Bolt
    {
        $this->versions = $v;
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
     * Version is available after successful connection with init/hello message
     * @return float
     */
    public function getProtocolVersion(): float
    {
        return $this->version;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function handshake(): bool
    {
        if (self::$debug)
            echo 'HANDSHAKE';

        $this->connection->write(chr(0x60) . chr(0x60) . chr(0xb0) . chr(0x17));
        $this->connection->write($this->packProtocolVersions());

        $this->unpackProtocolVersion();
        if (empty($this->version)) {
            throw new ConnectException('Wrong version');
        }

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $this->version);
        if (!class_exists($protocolClass)) {
            throw new ConnectException('Requested Protocol version (' . $this->version . ') not yet implemented');
        }
        $this->protocol = new $protocolClass($this->packer, $this->unpacker, $this->connection);

        return $this->protocol instanceof AProtocol;
    }

    /**
     * Read and compose selected protocol version
     */
    private function unpackProtocolVersion()
    {
        $result = [];

        foreach (str_split($this->connection->read(4)) as $ch)
            $result[] = unpack('C', $ch)[1] ?? 0;

        $result = array_filter($result);
        $result = array_reverse($result);
        $this->version = implode('.', $result);
    }

    /**
     * Pack requested protocol versions
     * @return string
     */
    private function packProtocolVersions(): string
    {
        $versions = [];

        while (count($this->versions) < 4)
            $this->versions[] = '0';

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

    /**
     * Send INIT message
     * @param auth\Auth $auth
     * @return bool|array
     * @throws Exception
     * @version <3
     */
    public function init(\Bolt\auth\Auth $auth)
    {
        if (!$this->connection->connect())
            return false;

        if (!$this->handshake())
            return false;

        if (self::$debug)
            echo 'INIT';

        return $this->protocol->init($auth->getCredentials(), $this->routing) ?? false;
    }

    /**
     * Send HELLO message
     * @param auth\Auth $auth
     * @return bool|array
     * @throws Exception
     * @version >=3
     */
    public function hello(\Bolt\auth\Auth $auth)
    {
        return $this->init($auth);
    }

    /**
     * Set routing table for HELLO message
     * @param array|null $routing routing::Dictionary(address::String)
     * <pre>null - the server should not carry out routing
     * [] - the server should carry out routing
     * ['address' => 'ip:port'] - the server should carry out routing according to the given routing context</pre>
     * @return Bolt
     */
    public function setRouting(?array $routing = null): Bolt
    {
        $this->routing = $routing;
        return $this;
    }

    /**
     * Send RUN message
     * @param string $statement
     * @param array $parameters
     * @param array $extra extra::Dictionary(bookmarks::List<String>, tx_timeout::Integer, tx_metadata::Dictionary, mode::String, db:String)
     * <pre>The bookmarks is a list of strings containg some kind of bookmark identification e.g [“neo4j-bookmark-transaction:1”, “neo4j-bookmark-transaction:2”]
     * The tx_timeout is an integer in that specifies a transaction timeout in ms.
     * The tx_metadata is a dictionary that can contain some metadata information, mainly used for logging.
     * The mode specifies what kind of server the RUN message is targeting. For write access use "w" and for read access use "r". Defaults to write access if no mode is sent.
     * The db specifies the database name for multi-database to select where the transaction takes place. If no db is sent or empty string it implies that it is the default database.</pre>
     * @return array
     * @throws Exception
     */
    public function run(string $statement, array $parameters = [], array $extra = []): array
    {
        if (self::$debug)
            echo 'RUN: ' . $statement;
        return $this->protocol->run($statement, $parameters, $extra);
    }

    /**
     * Send PULL_ALL message
     * @param int $n The n specifies how many records to fetch. n=-1 will fetch all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return array
     * @throws Exception
     * @version <4
     */
    public function pullAll(int $n = -1, int $qid = -1): array
    {
        if (self::$debug)
            echo 'PULL';
        return $this->protocol->pullAll(['n' => $n, 'qid' => $qid]);
    }

    /**
     * Send PULL message
     * @param int $n The n specifies how many records to fetch. n=-1 will fetch all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return array Array of records. Last array element is success message.
     * @throws Exception
     * @version >=4
     * @internal PULL_ALL alias
     */
    public function pull(int $n = -1, int $qid = -1): array
    {
        return $this->pullAll($n, $qid);
    }

    /**
     * Send DISCARD_ALL message
     * @param int $n The n specifies how many records to throw away. n=-1 will throw away all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return bool
     * @throws Exception
     * @version <4
     */
    public function discardAll(int $n = -1, int $qid = -1): bool
    {
        if (self::$debug)
            echo 'DISCARD';
        return $this->protocol->discardAll(['n' => $n, 'qid' => $qid]);
    }

    /**
     * Send DISCARD message
     * @param int $n The n specifies how many records to throw away. n=-1 will throw away all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return bool
     * @throws Exception
     * @version >=4
     * @internal DISCARD_ALL alias
     */
    public function discard(int $n = -1, int $qid = -1): bool
    {
        return $this->discardAll($n, $qid);
    }

    /**
     * Send BEGIN message
     * @param array $extra extra::Dictionary(bookmarks::List<String>, tx_timeout::Integer, tx_metadata::Dictionary, mode::String, db:String)
     * <pre>The bookmarks is a list of strings containg some kind of bookmark identification e.g [“neo4j-bookmark-transaction:1”, “neo4j-bookmark-transaction:2”]
     * The tx_timeout is an integer in that specifies a transaction timeout in ms.
     * The tx_metadata is a dictionary that can contain some metadata information, mainly used for logging.
     * The mode specifies what kind of server the RUN message is targeting. For write access use "w" and for read access use "r". Defaults to write access if no mode is sent.
     * The db specifies the database name for multi-database to select where the transaction takes place. If no db is sent or empty string it implies that it is the default database.</pre>
     * @return bool
     * @throws Exception
     * @version >=3
     */
    public function begin(array $extra = []): bool
    {
        if (self::$debug)
            echo 'BEGIN';
        return method_exists($this->protocol, 'begin') && $this->protocol->begin($extra);
    }

    /**
     * Send COMMIT message
     * @return bool
     * @throws Exception
     * @version >=3
     */
    public function commit(): bool
    {
        if (self::$debug)
            echo 'COMMIT';
        return method_exists($this->protocol, 'commit') && $this->protocol->commit();
    }

    /**
     * Send ROLLBACK message
     * @return bool
     * @throws Exception
     * @version >=3
     */
    public function rollback(): bool
    {
        if (self::$debug)
            echo 'ROLLBACK';
        return method_exists($this->protocol, 'rollback') && $this->protocol->rollback();
    }

    /**
     * Send RESET message
     * @return bool
     * @throws Exception
     */
    public function reset(): bool
    {
        if (self::$debug)
            echo 'RESET';
        return $this->protocol->reset();
    }

    /**
     * Send ROUTE message to instruct the server to return the current routing table.
     * @param array|null $routing
     * @param array $bookmarks
     * @param array|string|null $extra
     * @return array|null
     * @version >=4.3 In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     */
    public function route(?array $routing = null, array $bookmarks = [], $extra = null): ?array
    {
        if (self::$debug)
            echo 'ROUTE';
        $routing = $routing ?? ['address' => $this->connection->getIp() . ':' . $this->connection->getPort()];
        return method_exists($this->protocol, 'route') ? $this->protocol->route($routing, $bookmarks, $extra) : null;
    }

    /**
     * Say goodbye
     */
    public function __destruct()
    {
        if ($this->protocol instanceof AProtocol) {
            if (self::$debug)
                echo 'GOODBYE';
            method_exists($this->protocol, 'goodbye') && $this->protocol->goodbye();
        }

        $this->connection->disconnect();
    }
}

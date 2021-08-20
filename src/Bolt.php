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
     * @var string
     */
    private $scheme = 'basic';

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
    public function setPackStreamVersion(int $version = 1)
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
     * @param string $scheme
     * @return Bolt
     */
    public function setScheme(string $scheme = 'basic')
    {
        if (in_array($scheme, ['none', 'basic', 'kerberos']))
            $this->scheme = $scheme;
        return $this;
    }

    /**
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
     * @version <3
     * @param string $name should conform to "Name/Version" https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
     * @param string $user
     * @param string $password
     * @param array $routing routing::Dictionary(address::String)
     <pre>null - the server should not carry out routing
     [] - the server should carry out routing
     ['address' => 'ip:port'] - the server should carry out routing according to the given routing context</pre>
     * @param array $metadata Server success response metadata
     * @return bool
     * @throws Exception
     */
    public function init(string $name, string $user, string $password, array $routing = null, array &$metadata = []): bool
    {
        if (!$this->connection->connect())
            return false;

        if (!$this->handshake())
            return false;

        if (self::$debug)
            echo 'INIT';

        $metadata = $this->protocol->init($name, $this->scheme, $user, $password, $routing);
        return !empty($metadata);
    }

    /**
     * Send HELLO message
     * @internal INIT alias
     * @version >=3
     * @param string $name
     * @param string $user
     * @param string $password
     * @param array $routing routing::Dictionary(address::String)
    <pre>null - the server should not carry out routing
    [] - the server should carry out routing
    ['address' => 'ip:port'] - the server should carry out routing according to the given routing context</pre>
     * @param array $metadata Server success response metadata
     * @return bool
     * @throws Exception
     */
    public function hello(string $name, string $user, string $password, array $routing = null, array &$metadata = []): bool
    {
        return $this->init($name, $user, $password, $routing, $metadata);
    }

    /**
     * Send RUN message
     * @param string $statement
     * @param array $parameters
     * @param array $extra extra::Dictionary(bookmarks::List<String>, tx_timeout::Integer, tx_metadata::Dictionary, mode::String, db:String)
    <pre>The bookmarks is a list of strings containg some kind of bookmark identification e.g [“neo4j-bookmark-transaction:1”, “neo4j-bookmark-transaction:2”]
    The tx_timeout is an integer in that specifies a transaction timeout in ms.
    The tx_metadata is a dictionary that can contain some metadata information, mainly used for logging.
    The mode specifies what kind of server the RUN message is targeting. For write access use "w" and for read access use "r". Defaults to write access if no mode is sent.
    The db specifies the database name for multi-database to select where the transaction takes place. If no db is sent or empty string it implies that it is the default database.</pre>
     * @return array
     * @throws Exception
     */
    public function run(string $statement, array $parameters = [], array $extra = [])
    {
        if (self::$debug)
            echo 'RUN: ' . $statement;
        return $this->protocol->run($statement, $parameters, $extra);
    }

    /**
     * Send PULL_ALL message
     * @version <4
     * @param int $n The n specifies how many records to fetch. n=-1 will fetch all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return array
     * @throws Exception
     */
    public function pullAll(int $n = -1, int $qid = -1)
    {
        if (self::$debug)
            echo 'PULL';
        return $this->protocol->pullAll(['n' => $n, 'qid' => $qid]);
    }

    /**
     * Send PULL message
     * @version >=4
     * @internal PULL_ALL alias
     * @param int $n The n specifies how many records to fetch. n=-1 will fetch all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return array Array of records. Last array element is success message.
     * @throws Exception
     */
    public function pull(int $n = -1, int $qid = -1)
    {
        return $this->pullAll($n, $qid);
    }

    /**
     * Send DISCARD_ALL message
     * @version <4
     * @param int $n The n specifies how many records to throw away. n=-1 will throw away all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return bool
     * @throws Exception
     */
    public function discardAll(int $n = -1, int $qid = -1)
    {
        if (self::$debug)
            echo 'DISCARD';
        return $this->protocol->discardAll(['n' => $n, 'qid' => $qid]);
    }

    /**
     * Send DISCARD message
     * @version >=4
     * @internal DISCARD_ALL alias
     * @param int $n The n specifies how many records to throw away. n=-1 will throw away all records.
     * @param int $qid The qid (query identification) specifies the result of which statement the operation should be carried out. (Explicit Transaction only). qid=-1 can be used to denote the last executed statement and if no ``.
     * @return bool
     * @throws Exception
     */
    public function discard(int $n = -1, int $qid = -1): bool
    {
        return $this->discardAll($n, $qid);
    }

    /**
     * Send BEGIN message
     * @param array $extra extra::Dictionary(bookmarks::List<String>, tx_timeout::Integer, tx_metadata::Dictionary, mode::String, db:String)
    <pre>The bookmarks is a list of strings containg some kind of bookmark identification e.g [“neo4j-bookmark-transaction:1”, “neo4j-bookmark-transaction:2”]
    The tx_timeout is an integer in that specifies a transaction timeout in ms.
    The tx_metadata is a dictionary that can contain some metadata information, mainly used for logging.
    The mode specifies what kind of server the RUN message is targeting. For write access use "w" and for read access use "r". Defaults to write access if no mode is sent.
    The db specifies the database name for multi-database to select where the transaction takes place. If no db is sent or empty string it implies that it is the default database.</pre>
     * @return bool
     * @throws Exception
     */
    public function begin(array $extra = []): bool
    {
        if (self::$debug)
            echo 'BEGIN';
        return $this->protocol->begin($extra);
    }

    /**
     * Send COMMIT message
     * @return bool
     * @throws Exception
     */
    public function commit(): bool
    {
        if (self::$debug)
            echo 'COMMIT';
        return $this->protocol->commit();
    }

    /**
     * Send ROLLBACK message
     * @return bool
     * @throws Exception
     */
    public function rollback(): bool
    {
        if (self::$debug)
            echo 'ROLLBACK';
        return $this->protocol->rollback();
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
     * Say goodbye
     */
    public function __destruct()
    {
        if ($this->protocol instanceof AProtocol) {
            if (self::$debug)
                echo 'GOODBYE';
            $this->protocol->goodbye();
        }

        $this->connection->disconnect();
    }

    /**
     * fetch the current routing table, if the message specification allows it.
     *
     * @param array|null $routing
     *
     * @return array{rt: array{servers: list<array{addresses: list<string>, role: 'WRITE'|'READ'|'ROUTE'}>, ttl: int}}|null
     */
    public function route(?array $routing = null): ?array
    {
        if (!method_exists($this->protocol, 'route')) {
            return null;
        }

        $routing = $routing ?? ['address' => $this->connection->getIp() . ':' . $this->connection->getPort()];
        return $this->protocol->route($routing);
    }
}

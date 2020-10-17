<?php

namespace Bolt;

use Exception;
use Bolt\PackStream\{IPacker, IUnpacker};
use Bolt\protocol\AProtocol;

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
     * @var Socket
     */
    private $socket;

    /**
     * @var array
     */
    private $versions = [4.1, 4, 3];

    /**
     * @var float
     */
    private $version;

    /**
     * @var int
     */
    private $packStreamVersion = 1;

    /**
     * @var string
     */
    public static $scheme = 'basic';

    /**
     * Custom error handler instead of throwing Exceptions
     * @var callable (string message, string code)
     */
    public static $errorHandler;
    
    /**
     * Print debug info
     * @var bool 
     */
    public static $debug = false;

    /**
     * Bolt constructor
     * @param string $ip
     * @param int $port
     * @param int $timeout
     * @throws Exception
     */
    public function __construct(string $ip = '127.0.0.1', int $port = 7687, int $timeout = 15)
    {
        $this->socket = new Socket($ip, $port, $timeout);

        $packerClass = "\\Bolt\\PackStream\\v" . $this->packStreamVersion . "\\Packer";
        if (!class_exists($packerClass)) {
            Bolt::error('Requested PackStream version (' . $this->packStreamVersion . ') not yet implemented');
        } else {
            $this->packer = new $packerClass();
        }

        $unpackerClass = "\\Bolt\\PackStream\\v" . $this->packStreamVersion . "\\Unpacker";
        if (!class_exists($unpackerClass)) {
            Bolt::error('Requested PackStream version (' . $this->packStreamVersion . ') not yet implemented');
        } else {
            $this->unpacker = new $unpackerClass();
        }
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
     */
    public function setPackStreamVersion(int $version = 1)
    {
        $this->packStreamVersion = $version;
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

        $this->socket->write(chr(0x60) . chr(0x60) . chr(0xb0) . chr(0x17));
        $this->socket->write($this->packProtocolVersions());

        $this->unpackProtocolVersion();
        if (empty($this->version)) {
            static::error('Wrong version');
            return false;
        }

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $this->version);
        if (!class_exists($protocolClass)) {
            Bolt::error('Requested Protocol version (' . $this->version . ') not yet implemented');
        } else {
            $this->protocol = new $protocolClass($this->packer, $this->unpacker, $this->socket);
        }

        return $this->protocol instanceof AProtocol;
    }

    /**
     * Read and compose selected protocol version
     */
    private function unpackProtocolVersion()
    {
        $result = [];

        foreach (str_split($this->socket->readBuffer(4)) as $ch)
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
     * @param string $name
     * @param string $user
     * @param string $password
     * @param array $routing routing::Dictionary(address::String)
     <pre>null - the server should not carry out routing
     [] - the server should carry out routing
     ['address' => 'ip:port'] - the server should carry out routing according to the given routing context</pre>
     * @return bool
     * @throws Exception
     */
    public function init(string $name, string $user, string $password, array $routing = null): bool
    {
        if (!$this->handshake())
            return false;

        if (self::$debug)
            echo 'INIT';

        return $this->protocol->init($name, Bolt::$scheme, $user, $password, $routing);
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
     * @return bool
     * @throws Exception
     */
    public function hello(string $name, string $user, string $password, array $routing = null): bool
    {
        return $this->init($name, $user, $password, $routing);
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
     * @return mixed Return false on error
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
     * @return mixed Array of records or false on error. Last array element is success message.
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
     * @return mixed Array of records or false on error. Last array element is success message.
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
     */
    public function reset(): bool
    {
        if (self::$debug)
            echo 'RESET';
        return $this->protocol->reset();
    }

    /**
     * Process error
     * @param string $msg
     * @param string $code
     * @throws Exception
     */
    public static function error(string $msg, string $code = '')
    {
        if (is_callable(self::$errorHandler)) {
            call_user_func(self::$errorHandler, $msg, $code);
        } else {
            if (!empty($code)) {
                $msg .= ' (' . $code . ')';
            }
            throw new Exception($msg);
        }
    }
    
    /**
     * Print buffer as HEX
     * @param string $str
     * @param bool $write
     */
    public static function printHex(string $str, bool $write = true)
    {
        $str = implode(unpack('H*', $str));
        echo '<pre>';
        echo $write ? '> ' : '< ';
        foreach (str_split($str, 8) as $chunk) {
            echo implode(' ', str_split($chunk, 2));
            echo '    ';
        }
        echo '</pre>';
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
    }

}

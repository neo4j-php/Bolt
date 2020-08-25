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
        Socket::initialize($ip, $port, $timeout);

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

        Socket::write(chr(0x60) . chr(0x60) . chr(0xb0) . chr(0x17));
        Socket::write($this->packProtocolVersions());

        $this->unpackProtocolVersion();
        if (empty($this->version)) {
            $this->error('Wrong version');
            return false;
        }

        $protocolClass = "\\Bolt\\protocol\\V" . str_replace('.', '_', $this->version);
        if (!class_exists($protocolClass)) {
            Bolt::error('Requested Protocol version (' . $this->version . ') not yet implemented');
        } else {
            $this->protocol = new $protocolClass($this->packer, $this->unpacker);
        }

        return $this->protocol instanceof AProtocol;
    }

    /**
     * Read and compose selected protocol version
     */
    private function unpackProtocolVersion()
    {
        $result = [];

        foreach (str_split(Socket::readBuffer(4)) as $ch)
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
     * @return bool
     * @throws Exception
     */
    public function init(string $name, string $user, string $password): bool
    {
        if (!$this->handshake())
            return false;

        if (self::$debug)
            echo 'INIT';

        return $this->protocol->init($name, Bolt::$scheme, $user, $password);
    }

    /**
     * Send HELLO message
     * @internal INIT alias
     * @version >=3
     * @param string $name
     * @param string $user
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function hello(string $name, string $user, string $password): bool
    {
        return $this->init($name, $user, $password);
    }

    /**
     * Send RUN message
     * @param string $statement
     * @param array $parameters
     * @param array $extra
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
     * @return mixed Array of records or false on error. Last array element is success message.
     */
    public function pullAll()
    {
        if (self::$debug)
            echo 'PULL';
        return $this->protocol->pullAll();
    }

    /**
     * Send PULL message
     * @version >=4
     * @internal PULL_ALL alias
     * @return mixed Array of records or false on error. Last array element is success message.
     */
    public function pull()
    {
        return $this->pullAll();
    }

    /**
     * Send DISCARD_ALL message
     * @version <4
     * @return bool
     */
    public function discardAll()
    {
        if (self::$debug)
            echo 'DISCARD';
        return $this->protocol->discardAll();
    }

    /**
     * Send DISCARD message
     * @version >=4
     * @internal DISCARD_ALL alias
     * @return bool
     */
    public function discard(): bool
    {
        return $this->discardAll();
    }

    /**
     * Send BEGIN message
     * @param array $extra
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
     * Close socket
     */
    public function __destruct()
    {
        if ($this->protocol instanceof AProtocol) {
            if (self::$debug)
                echo 'GOODBYE';
            $this->protocol->goodbye();
        }

        @socket_close(Socket::$socket);
    }

}

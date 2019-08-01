<?php

namespace Bolt;

use Exception;

/**
 * Class Bolt
 * Bolt protocol library using TCP socket connection
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 */
class Bolt
{
    private const SUCCESS = 0x70;
    private const FAILURE = 0x7F;
    private const IGNORED = 0x7E;
    private const RECORD = 0x71;

    /**
     * @var Packer
     */
    private $packer;
    
    /**
     * @var Unpacker
     */
    private $unpacker;

    /**
     * @var resource
     */
    private $socket;
    
    /**
     * Throwing Exceptions if not set
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
        if (!extension_loaded('sockets')) {
            die('PHP Extension sockets not enabled');
        }
        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!is_resource($this->socket)) {
            $this->error('Cannot create socket');
            return;
        }

        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $timeout, 'usec' => 0]);

        $conn = socket_connect($this->socket, $ip, $port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            $this->error(socket_strerror($code), $code);
            return;
        }

        $this->packer = new Packer();
        $this->unpacker = new Unpacker();
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function handshake(): bool
    {
        $this->write(chr(0x60).chr(0x60).chr(0xb0).chr(0x17));
        
        //version
        $this->write(pack('N', 2) . pack('N', 1) . pack('N', 0) . pack('N', 0));
        $version = unpack('N', $this->readBuffer(4))[1] ?? 0;
        if ($version == 0) {
            $this->error('Wrong version');
            return false;
        }
        
        return true;
    }

    /**
     * Send INIT message
     * @param string $name
     * @param string $user
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function init(string $name, string $user, string $password): bool
    {
        if (!$this->handshake()) {
            return false;
        }
        
        try {
            $msg = $this->packer->pack(0x01, $name, [
                'scheme' => 'basic',
                'principal' => $user,
                'credentials' => $password
            ]);
        } catch (Exception $ex) {
            $this->error($ex->getMessage());
            return false;
        }
        
        $this->write($msg);

        list($signature, $output) = $this->read();
        if ($signature == self::FAILURE) {
            try {
                $msg = $this->packer->pack(0x0E);
            } catch (Exception $ex) {
                $this->error($msg);
                return false;
            }
            
            //AckFailure after init do not respond with any message
            $this->write($msg);
            $this->error($output['message'], $output['code']);
        }
        
        return $signature == self::SUCCESS;
    }

    /**
     * Send RUN message
     * @param string $statement
     * @param array $parameters
     * @return mixed Return false on error
     * @throws Exception
     */
    public function run(string $statement, array $parameters = [])
    {
        try {
            $msg = $this->packer->pack(0x10, $statement, $parameters);
        } catch (Exception $ex) {
            $this->error($ex->getMessage());
            return false;
        }
        
        $this->write($msg);
        
        list($signature, $output) = $this->read();
        if ($signature == self::FAILURE) {
            $this->ackFailure();
            $this->error($output['message'], $output['code']);
        }
        return $signature == self::SUCCESS ? $output : false;
    }

    /**
     * Send DISCARD_ALL message
     * @return bool
     */
    public function discardAll(): bool
    {
        try {
            $msg = $this->packer->pack(0x2F);
        } catch (Exception $ex) {
            $this->error($ex->getMessage());
            return false;
        }
        
        $this->write($msg);
        
        list($signature,) = $this->read();
        return $signature == self::SUCCESS;
    }

    /**
     * Send PULL_ALL message
     * Last success message contains key "type" which describe operation: read (r), write (w), read/write (rw) or schema write (s)
     * @return mixed Array of records or false on error. Last array element is success message.
     * @throws Exception
     */
    public function pullAll()
    {
        try {
            $msg = $this->packer->pack(0x3F);
        } catch (Exception $ex) {
            $this->error($ex->getMessage());
            return false;
        }
        
        $this->write($msg);
        
        $output = [];
        do {
            list($signature, $ret) = $this->read();
            $output[] = $ret;
        } while ($signature == self::RECORD);
        
        if ($signature == self::FAILURE) {
            $this->ackFailure();
            $this->error($ret['message'], $ret['code']);
            $output = false;
        }
        
        return $output;
    }

    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     * @return bool
     * @throws Exception
     */
    private function ackFailure(): bool
    {
        try {
            $msg = $this->packer->pack(0x0E);
        } catch (Exception $ex) {
            $this->error($ex->getMessage());
            return false;
        }
        
        $this->write($msg);
        
        list($signature,) = $this->read();
        return $signature == self::SUCCESS;
    }

    /**
     * Send RESET message
     * @return bool
     * @throws Exception
     */
    public function reset(): bool
    {
        try {
            $msg = $this->packer->pack(0x0F);
        } catch (Exception $ex) {
            $this->error($ex->getMessage());
            return false;
        }
        
        $this->write($msg);
        
        list($signature,) = $this->read();
        return $signature == self::SUCCESS;
    }

    /**
     * Socket read wrapper with message chunk support to process received message
     * @return array [signature, output]
     * @throws Exception
     */
    private function read()
    {
        $msg = '';
        while (true) {
            $header = $this->readBuffer(2);
            if (ord($header[0]) == 0x00 && ord($header[1]) == 0x00) {
                break;
            }
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->readBuffer($length);
        }

        $output = null;
        $signature = 0;
        if (!empty($msg)) {
            if (self::$debug) {
                self::printHex($msg, false);
            }
            
            try {
                $output = $this->unpacker->unpack($msg, $signature);
            } catch (Exception $ex) {
                $this->error($ex->getMessage());
            }
        }

        return [$signature, $output];
    }
    
    /**
     * @param int $length
     * @return string
     */
    private function readBuffer(int $length = 2048): string
    {
        $output = '';
        do {
            $output .= socket_read($this->socket, $length - mb_strlen($output, '8bit'), PHP_BINARY_READ);
        } while (mb_strlen($output, '8bit') < $length);
        return $output;
    }

    /**
     * @param string $buffer
     * @throws Exception
     */
    private function write(string $buffer)
    {
        $size = mb_strlen($buffer, '8bit');
        $sent = 0;
        
        if (self::$debug) {
            self::printHex($buffer);
        }
        
        while ($sent < $size) {
            $sent = socket_write($this->socket, $buffer, $size);
            if ($sent === false) {
                $code = socket_last_error($this->socket);
                $this->error(socket_strerror($code), $code);
                return;
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    /**
     * Process error
     * @param string $msg
     * @param int $code
     * @throws Exception
     */
    private function error(string $msg, string $code = '')
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
        @socket_close($this->socket);
    }

}

<?php

require_once 'Packer.php';

/**
 * Class Bolt
 * Bolt protocol library using TCP socket connection
 *
 * @author Michal Stefanak
 */
class Bolt
{
    /**
     * @var Packer
     */
    private $packer;

    /**
     * @var resource
     */
    private $socket;

    /**
     * Bolt constructor
     * @param string $ip
     * @param int $port
     * @param int $timeout
     * @throws Exception
     */
    public function __construct(string $ip = '127.0.0.1', int $port = 7687, int $timeout = 15)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!is_resource($this->socket)) {
            throw new Exception('Cannot create socket');
        }

        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));

        $conn = socket_connect($this->socket, $ip, $port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            throw new Exception(socket_strerror($code), $code);
        }

        $this->handshake();

        $this->packer = new Packer();
    }

    /**
     * @throws Exception
     */
    private function handshake()
    {
        $this->write(chr(0x60).chr(0x60).chr(0xb0).chr(0x17));
        //version
        $this->write(pack('N', 1) . pack('N', 0) . pack('N', 0) . pack('N', 0));
        $version = unpack('N', $this->readBuffer(4))[1] ?? 0;
        if ($version == 0) {
            throw new Exception('Wrong version');
        }
    }

    /**
     * Send INIT message
     * @param string $name
     * @param string $user
     * @param string $password
     * @return mixed
     * @throws Exception
     */
    public function init(string $name, string $user, string $password)
    {
        $this->write($this->packer->pack(0x01, $name, [
            'scheme' => 'basic',
            'principal' => $user,
            'credentials' => $password
        ]));

        return $this->read();
    }

    /**
     * Send RUN message
     * @param string $statement
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function run(string $statement, array $parameters = [])
    {
        $this->write($this->packer->pack(0x10, $statement, $parameters));
        return $this->read();
    }

    private function debug(string $str)
    {
        $str = implode(unpack('H*', $str));
        echo '<pre>';
        foreach (str_split($str, 8) as $chunk) {
            echo implode(' ', str_split($chunk, 2));
            echo '    ';
        }
        echo '</pre>';
    }

    //@todo
    public function discardAll()
    {
        $this->write($this->packer->pack(0x2F));
        return $this->read();
    }

    /**
     * Send PULL_ALL message
     * @return mixed
     * @throws Exception
     */
    public function pullAll()
    {
        $this->write($this->packer->pack(0x3F));
        return $this->read();
    }

    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     * @throws Exception
     */
    private function ackFailure()
    {
        $this->write($this->packer->pack(0x0E));
        $this->read();
    }

    //@todo
    public function reset()
    {
        $this->write($this->packer->pack(0x0F));
        return $this->read();
    }

    /**
     * Socket read wrapper with message chunk support to process types of received message
     * @return mixed
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
        if (!empty($msg)) {
            $signature = 0;
            $output = $this->packer->unpack($msg, $signature);
            switch ($signature) {
                case 0x70: //SUCCESS
                    break;
                case 0x7F: //FAILURE
                    $this->ackFailure();
                    throw new Exception($output['message'] . ' (' . $output['code'] . ')');
                    break;
                case 0x7E: //IGNORED
                    break;
                case 0x71: //RECORD
                    break;
            }
        }

        return $output;
    }

    /**
     * @param string $buffer
     * @throws Exception
     */
    public function write(string $buffer)
    {
        $size = mb_strlen($buffer, '8bit');
        $sent = 0;
        while ($sent < $size) {
            $sent = socket_write($this->socket, $buffer, $size);
            if ($sent === false) {
                throw new Exception(socket_last_error($this->socket));
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    /**
     * @param int $length
     * @return string
     */
    public function readBuffer(int $length = 2048): string
    {
        $output = '';
        do {
            $output .= socket_read($this->socket, $length - mb_strlen($output, '8bit'), PHP_BINARY_READ);
        } while (mb_strlen($output, '8bit') < $length);
        return $output;
    }

    public function __destruct()
    {
        @socket_close($this->socket);
    }

}

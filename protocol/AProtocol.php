<?php

namespace Bolt\protocol;

use Bolt\Bolt;
use Bolt\PackStream\{IPacker, IUnpacker};
use Bolt\Socket;
use Exception;

/**
 * Abstract class AProtocol
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
abstract class AProtocol implements IProtocol
{

    protected const SUCCESS = 0x70;
    protected const FAILURE = 0x7F;
    protected const IGNORED = 0x7E;
    protected const RECORD = 0x71;

    /**
     * @var IPacker
     */
    protected $packer;

    /**
     * @var IUnpacker
     */
    protected $unpacker;

    /**
     * @var Socket
     */
    private $socket;

    /**
     * AProtocol constructor.
     * @param IPacker $packer
     * @param IUnpacker $unpacker
     * @param Socket $socket
     */
    public function __construct(IPacker $packer, IUnpacker $unpacker, Socket $socket)
    {
        $this->packer = $packer;
        $this->unpacker = $unpacker;
        $this->socket = $socket;
    }

    public function begin(...$args): bool
    {
        return false;
    }

    public function commit(...$args): bool
    {
        return false;
    }

    public function rollback(...$args): bool
    {
        return false;
    }

    public function goodbye(...$args)
    {

    }

    /**
     * Write to socket
     * @param string $buffer
     * @throws Exception
     */
    protected function write(string $buffer)
    {
        $this->socket->write($buffer);
    }

    /**
     * Read from socket
     * @param int|null $signature
     * @return mixed|null
     * @throws Exception
     */
    protected function read(?int &$signature)
    {
        $msg = '';
        while (true) {
            $header = $this->socket->read(2);
            if (ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->socket->read($length);
        }

        $output = null;
        $signature = 0;
        if (!empty($msg)) {
            try {
                $output = $this->unpacker->unpack($msg, $signature);
            } catch (Exception $ex) {
                Bolt::error($ex->getMessage());
            }
        }

        return $output;
    }

}

<?php

namespace Bolt\protocol;

use Generator;
use Bolt\PackStream\{IPacker, IUnpacker};
use Bolt\connection\IConnection;
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
     * @var IConnection
     */
    private $connection;

    /**
     * AProtocol constructor.
     * @param IPacker $packer
     * @param IUnpacker $unpacker
     * @param IConnection $connection
     */
    public function __construct(IPacker $packer, IUnpacker $unpacker, IConnection $connection)
    {
        $this->packer = $packer;
        $this->unpacker = $unpacker;
        $this->connection = $connection;
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
     * Write to connection
     * @param Generator $generator
     * @throws Exception
     */
    protected function write(Generator $generator)
    {
        foreach ($generator as $buffer)
            $this->connection->write($buffer);
    }

    /**
     * Read from connection
     * @param int|null $signature
     * @return mixed|null
     * @throws Exception
     */
    protected function read(?int &$signature)
    {
        $msg = '';
        while (true) {
            $header = $this->connection->read(2);
            if (ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->connection->read($length);
        }

        $output = null;
        $signature = 0;
        if (!empty($msg)) {
            $output = $this->unpacker->unpack($msg, $signature);
        }

        return $output;
    }

}

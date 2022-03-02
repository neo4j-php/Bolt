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
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
abstract class AProtocol
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
    protected $connection;

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
            if ($msg !== '' && ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->connection->read($length);
        }

        $output = null;
        $signature = 0;
        if (!empty($msg)) {
            $output = $this->unpacker->unpack($msg);
            $signature = $this->unpacker->getSignature();
        }

        return $output;
    }

    /**
     * Returns the bolt protocol version as a string.
     * @return string
     */
    public function getVersion(): string
    {
        if (preg_match("/V([\d_]+)$/", static::class, $match)) {
            return str_replace('_', '.', $match[1]);
        }

        trigger_error('Protocol version class name is not valid', E_USER_ERROR);
    }
}

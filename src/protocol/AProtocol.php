<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
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

    protected IPacker $packer;
    protected IUnpacker $unpacker;
    protected IConnection $connection;

    public ServerState $serverState;

    protected array $pipelinedMessages = [];

    /**
     * AProtocol constructor.
     * @param IPacker $packer
     * @param IUnpacker $unpacker
     * @param IConnection $connection
     * @param ServerState $serverState
     */
    public function __construct(IPacker $packer, IUnpacker $unpacker, IConnection $connection, ServerState $serverState)
    {
        $this->packer = $packer;
        $this->unpacker = $unpacker;
        $this->connection = $connection;
        $this->serverState = $serverState;
    }

    /**
     * Write to connection
     * @param iterable $generator
     * @throws Exception
     */
    protected function write(iterable $generator)
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

    /**
     * Fetch all responses from pipelined (executed) messages
     * @return array
     * @throws Exception
     */
    public function fetchPipelineResponse(): array
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        $output = [];

        foreach ($this->pipelinedMessages as $message) {
            $response = $this->read($signature);

            switch ($signature) {
                case self::RECORD:
                    $records = [$response];
                    do {
                        $record = $this->read($signature);
                        $records[] = $record;
                    } while ($signature == self::RECORD);
                    $output[] = $records;
                    break;

                case self::FAILURE:
                    $output[] = new MessageException($response['message'], $response['code']);
                    break;

                case self::IGNORED:
                    $output[] = new IgnoredException($message);
                    break;

                default:
                    $output[] = $response;
            }
        }

        $this->pipelinedMessages = [];

        if (end($output) instanceof MessageException) {
            $this->serverState->set(ServerState::FAILED);
        } elseif (end($output) instanceof IgnoredException) {
            $this->serverState->set(ServerState::INTERRUPTED);
        } else {
            $this->serverState->set($this->serverState->get() == ServerState::READY || $this->serverState->get() == ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
        }

        return $output;
    }

    /**
     * @throws Exception
     */
    protected function flushPipelineResponse()
    {
        if (count($this->pipelinedMessages) > 0) {
            $this->fetchPipelineResponse();
        }
    }
}

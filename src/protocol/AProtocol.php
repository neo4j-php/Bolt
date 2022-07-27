<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\protocol\ServerState\IServerState;
use Bolt\protocol\ServerState\ServerStateFactory;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
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

    private IServerState $serverState;
    private ServerStateFactory $serverStateFactory;

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
        $this->serverStateFactory = ServerStateFactory::createFromProtocol($this);
        $this->serverState = $this->serverStateFactory->buildNewState(ServerStates::CONNECTED);
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
     * Writes an action to the server and interpret/return the response.
     *
     * @param int $messageSignature
     * @param ...$toPack
     *
     * @return mixed|null
     * @throws Exception
     */
    protected function io(int $messageSignature, ...$toPack)
    {
        $this->write($this->packer->pack($messageSignature, ...$toPack));

        $data = $this->read($responseSignature);

        $this->interpretResult($messageSignature, $responseSignature, $data);

        return $data;
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

    public function getAssumedServerState(): IServerState
    {
        return $this->serverState;
    }

    /**
     * @return void
     * @throws IgnoredException
     * @throws MessageException
     */
    protected function interpretResult(int $messageSignature, ?int $responseSignature, $data): void
    {
        if ($responseSignature === self::IGNORED) {
            throw new IgnoredException('RUN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        $state = $this->serverState->transitionFromMessage(
            $messageSignature,
            $responseSignature,
            $data ?? []
        );

        $this->serverState = $this->serverStateFactory->buildNewState($state);

        if ($responseSignature === self::FAILURE) {
            throw new MessageException($data['message'], $data['code']);
        }
    }
}

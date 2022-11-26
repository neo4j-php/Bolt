<?php

namespace Bolt\protocol;

use Bolt\packstream\{IPacker, IUnpacker};
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
    /** @var string[] */
    protected array $pipelinedMessages = [];

    public function __construct(
        protected IPacker     $packer,
        protected IUnpacker   $unpacker,
        protected IConnection $connection,
        public ServerState    $serverState
    )
    {
        if (method_exists($this, 'setAvailableStructures')) {
            $this->setAvailableStructures();
        }
    }

    /**
     * Write to connection
     * @throws Exception
     */
    protected function write(iterable $generator): void
    {
        foreach ($generator as $buffer)
            $this->connection->write($buffer);
    }

    /**
     * Read from connection
     * @throws Exception
     */
    protected function read(?int &$signature): array
    {
        $msg = '';
        while (true) {
            $header = $this->connection->read(2);
            if ($msg !== '' && ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->connection->read($length);
        }

        $output = [];
        $signature = 0;
        if (!empty($msg)) {
            $output = $this->unpacker->unpack($msg);
            $signature = $this->unpacker->getSignature();

            if ($signature == Response::SIGNATURE_FAILURE) {
                $this->serverState->set(ServerState::FAILED);
            } elseif ($signature == Response::SIGNATURE_IGNORED) {
                $this->serverState->set(ServerState::INTERRUPTED);
                $output = [];
            }
        }

        return $output;
    }

    /**
     * Returns the bolt protocol version as a string.
     */
    public function getVersion(): string
    {
        if (preg_match("/V([\d_]+)$/", static::class, $match)) {
            return str_replace('_', '.', $match[1]);
        }

        trigger_error('Protocol version class name is not valid', E_USER_ERROR);
    }

    /**
     * Read responses from host output buffer.
     */
    public function getResponses(): \Iterator
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        while (count($this->pipelinedMessages) > 0) {
            $message = reset($this->pipelinedMessages);
            yield from $this->{'_' . $message}();
            array_shift($this->pipelinedMessages);
        }
    }

    /**
     * Read one response from host output buffer
     */
    public function getResponse(): Response
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        $message = reset($this->pipelinedMessages);
        /** @var Response $response */
        $response = $this->{'_' . $message}()->current();
        if ($response->getSignature() != Response::SIGNATURE_RECORD)
            array_shift($this->pipelinedMessages);
        return $response;
    }
}

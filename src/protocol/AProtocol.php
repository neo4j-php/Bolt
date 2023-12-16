<?php

namespace Bolt\protocol;

use Bolt\enum\Signature;
use Bolt\error\BoltException;
use Bolt\error\PackException;
use Bolt\error\UnpackException;
use Bolt\packstream\{IPacker, IUnpacker};
use Bolt\connection\IConnection;
use Bolt\error\ConnectException;
use Bolt\enum\ServerState as SS;

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

    protected IPacker $packer;
    protected IUnpacker $unpacker;

    /**
     * @throws UnpackException
     * @throws PackException
     */
    public function __construct(
        int                   $packStreamVersion,
        protected IConnection $connection,
        public ServerState    $serverState
    )
    {
        $packerClass = "\\Bolt\\packstream\\v" . $packStreamVersion . "\\Packer";
        if (!class_exists($packerClass)) {
            throw new PackException('Requested PackStream version (' . $packStreamVersion . ') not yet implemented');
        }
        $this->packer = new $packerClass($this->packStructuresLt ?? []);

        $unpackerClass = "\\Bolt\\packstream\\v" . $packStreamVersion . "\\Unpacker";
        if (!class_exists($unpackerClass)) {
            throw new UnpackException('Requested PackStream version (' . $packStreamVersion . ') not yet implemented');
        }
        $this->unpacker = new $unpackerClass($this->unpackStructuresLt ?? []);
    }

    /**
     * Write to connection
     * @throws ConnectException
     */
    protected function write(iterable $generator): void
    {
        foreach ($generator as $buffer)
            $this->connection->write($buffer);
    }

    /**
     * Read from connection
     * @throws BoltException
     */
    protected function read(?Signature &$signature = Signature::NONE): array
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
        if (!empty($msg)) {
            $output = $this->unpacker->unpack($msg);
            $s = $this->unpacker->getSignature();
            $signature = Signature::from($s);

            if ($signature == Signature::SUCCESS) {
                $this->serverState->set(SS::FAILED);
            } elseif ($signature == Signature::IGNORED) {
                $this->serverState->set(SS::INTERRUPTED);
                // Ignored doesn't have any response content
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
        $this->serverState->is(SS::READY, SS::TX_READY, SS::STREAMING, SS::TX_STREAMING);
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
        $this->serverState->is(SS::READY, SS::TX_READY, SS::STREAMING, SS::TX_STREAMING);
        $message = reset($this->pipelinedMessages);
        /** @var Response $response */
        $response = $this->{'_' . $message}()->current();
        if ($response->signature != Signature::RECORD)
            array_shift($this->pipelinedMessages);
        return $response;
    }
}

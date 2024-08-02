<?php

namespace Bolt\protocol;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\error\BoltException;
use Bolt\error\PackException;
use Bolt\error\UnpackException;
use Bolt\packstream\{IPacker, IUnpacker};
use Bolt\connection\IConnection;
use Bolt\error\ConnectException;

/**
 * Abstract class AProtocol
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
abstract class AProtocol
{
    /** @var Message[] */
    protected array $pipelinedMessages = [];

    protected IPacker $packer;
    protected IUnpacker $unpacker;

    public ServerState $serverState;

    private int $writeCalls = 0;

    /**
     * Multiple RUN statements in transaction generates "streams" which are pulled or discarded
     * We are keeping track of open streams to keep valid Server State
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#transaction
     * @var int
     */
    protected int $openStreams = 0;

    /**
     * @throws UnpackException
     * @throws PackException
     */
    public function __construct(
        int                   $packStreamVersion,
        protected IConnection $connection,
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
        $this->writeCalls++;
        foreach ($generator as $buffer) {
            $this->connection->write($buffer);
        }
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

            if ($signature == Signature::IGNORED) {
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
        while (count($this->pipelinedMessages) > 0) {
            yield $this->getResponse();
        }
    }

    /**
     * Read one response from host output buffer
     */
    public function getResponse(): Response
    {
        $serverState = $this->serverState;

        $message = reset($this->pipelinedMessages);
        if ($message === false)
            throw new ConnectException('No response waiting to be consumed');

        $methodName = '_' . (
            str_contains($message->name, '_')
                ? preg_replace("/_([a-z])/", "strtoupper('$1')", strtolower($message->name))
                : strtolower($message->name)
            );
        if (method_exists($this, $methodName)) {
            /** @var Response $response */
            $response = $this->$methodName()->current();
        } else {
            $content = $this->read($signature);
            $response = new Response($message, $signature, $content);
        }

        if ($response->signature != Signature::RECORD)
            array_shift($this->pipelinedMessages);

        foreach (($this->serverStateTransition ?? []) as $transition) {
            if ($transition[0] === $serverState && $transition[1] === $response->message && $transition[2] === $response->signature) {
                $this->serverState = $transition[3];
                if (in_array($response->message, [Message::PULL, Message::DISCARD], true)
                    && $response->signature === Signature::SUCCESS
                    && (($response->content['has_more'] ?? false) || $this->openStreams))
                    $this->serverState = $this->serverState === ServerState::TX_READY ? ServerState::TX_STREAMING : ServerState::STREAMING;
                if ($transition[3] === ServerState::DEFUNCT)
                    $this->connection->disconnect();
                break;
            }
        }

        return $response;
    }

    public function __destruct()
    {
        if (!getenv('BOLT_ANALYTICS_OPTOUT') && is_writable($_ENV['TEMP_DIR']. DIRECTORY_SEPARATOR)) {
            $file = $_ENV['TEMP_DIR'] . DIRECTORY_SEPARATOR . 'php-bolt-analytics' . DIRECTORY_SEPARATOR . 'analytics.' . strtotime('today') . '.json';
            $data = file_exists($file) ? (array)json_decode((string)file_get_contents($file), true) : [];
            $data['queries'] = ($data['queries'] ?? 0) + $this->writeCalls;
            $data['sessions'] = ($data['sessions'] ?? 0) + 1;
            file_put_contents($file, json_encode($data));
        }
    }
}

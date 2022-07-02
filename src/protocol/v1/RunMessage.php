<?php

namespace Bolt\protocol\v1;

use Bolt\connection\IConnection;
use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\PackStream\IPacker;
use Bolt\PackStream\IUnpacker;
use Exception;
use Generator;

/**
 * @method write(Generator $generator)
 * @method read(?int &$signature)
 * @property IPacker $packer
 * @property IUnpacker $unpacker
 * @property IConnection $connection;
 */
trait RunMessage
{
    /**
     * Send RUN message
     * A RUN message submits a new query for execution, the result of which will be consumed by a subsequent message, such as PULL_ALL.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---run
     * @param string $query
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function run(string $query, array $parameters = []): array
    {
        $this->write($this->packer->pack(0x10, $query, (object)$parameters));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            if (method_exists($this, 'ackFailure'))
                $this->ackFailure();
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('RUN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}

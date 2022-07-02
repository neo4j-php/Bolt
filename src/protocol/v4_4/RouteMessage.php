<?php

namespace Bolt\protocol\v4_4;

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
trait RouteMessage
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---44---route
     * @param array $routing
     * @param array $bookmarks
     * @param array $extra
     * @return array
     * @throws Exception
     */
    public function route(array $routing, array $bookmarks = [], array $extra = []): array
    {
        $this->write($this->packer->pack(0x66, (object)$routing, $bookmarks, (object)$extra));
        $message = $this->read($signature);

        if ($signature === self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('ROUTE message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}

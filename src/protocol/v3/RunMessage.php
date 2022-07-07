<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Exception;

trait RunMessage
{
    /**
     * Send RUN message
     * The RUN message requests that a Cypher query is executed with a set of parameters and additional extra data.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---run
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---run
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---run---44
     * @param string $query
     * @param array $parameters
     * @param array $extra
     * @return array
     * @throws Exception
     */
    public function run(string $query, array $parameters = [], array $extra = []): array
    {
        $this->write($this->packer->pack(
            0x10,
            $query,
            (object)$parameters,
            (object)$extra
        ));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('RUN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}

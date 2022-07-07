<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Exception;

trait BeginMessage
{
    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---begin
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---begin
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---begin---44
     * @param array $extra
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function begin(array $extra = []): array
    {
        $this->write($this->packer->pack(0x11, (object)$extra));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('BEGIN message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}

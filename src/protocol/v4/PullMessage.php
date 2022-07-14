<?php

namespace Bolt\protocol\v4;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Exception;

trait PullMessage
{
    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---pull
     * @param array $extra [n::Integer, qid::Integer]
     * @return array
     * @throws Exception
     */
    public function pull(array $extra = []): array
    {
        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x3F, $extra));

        $output = [];
        do {
            $message = $this->read($signature);
            $output[] = $message;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $last = array_pop($output);
            throw new MessageException($last['message'], $last['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('PULL message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $output;
    }
}
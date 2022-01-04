<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Exception;
use Bolt\error\MessageException;

/**
 * Class Protocol version 4
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-4.html
 * @package Bolt\protocol
 */
class V4 extends V3
{

    /**
     * @inheritDoc
     * @deprecated Renamed to PULL
     */
    public function pullAll(...$args): array
    {
        return $this->pull(...$args);
    }

    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---pull
     * @param array ...$args
     * @return array
     * @throws Exception
     */
    public function pull(...$args): array
    {
        if (count($args) == 0)
            $args[0] = ['n' => -1];
        elseif (!array_key_exists('n', $args[0]))
            $args[0]['n'] = -1;

        $this->write($this->packer->pack(0x3F, $args[0]));

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

    /**
     * @inheritDoc
     * @deprecated Renamed to DISCARD
     */
    public function discardAll(...$args): array
    {
        return $this->discard(...$args);
    }

    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---discard
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function discard(...$args): array
    {
        if (count($args) == 0)
            $args[0] = ['n' => -1];
        elseif (!array_key_exists('n', $args[0]))
            $args[0]['n'] = -1;

        $this->write($this->packer->pack(0x2F, $args[0]));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('DISCARD message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}

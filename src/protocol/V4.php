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
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-40
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
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param array ...$args
     * @return array
     * @throws Exception
     */
    public function pull(...$args): array
    {
        if (count($args) === 0)
            $args[0] = ['n' => -1];
        elseif (!array_key_exists('n', $args[0]))
            $args[0]['n'] = -1;

        $this->write($this->packer->pack(Signatures::PULL, $args[0]));

        $output = [];
        do {
            $last = $this->read($signature);
            $output[] = $last;
        } while ($signature === self::RECORD);

        $this->interpretResult(Signatures::PULL, $signature, $last);

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
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function discard(...$args): array
    {
        if (count($args) === 0)
            $args[0] = ['n' => -1];
        elseif (!array_key_exists('n', $args[0]))
            $args[0]['n'] = -1;

        return $this->io(Signatures::DISCARD, $args[0]);
    }
}

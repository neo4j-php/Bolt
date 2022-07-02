<?php

namespace Bolt\protocol\v4_1;

use Bolt\connection\IConnection;
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
trait HelloMessage
{
    use \Bolt\protocol\v3\HelloMessage {
        \Bolt\protocol\v3\HelloMessage::hello as __hello;
    }

    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---41---hello
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---43---hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropiate array
     * @return array
     * @throws Exception
     */
    public function hello(array $extra): array
    {
        if (isset($extra['routing']) && is_array($extra['routing']))
            $extra['routing'] = (object)$extra['routing'];

        return $this->__hello($extra);
    }
}

<?php

namespace Bolt\protocol\v3;

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
trait GoodbyeMessage
{
    /**
     * Send GOODBYE message
     * The GOODBYE message notifies the server that the connection is terminating gracefully.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---goodbye
     * @throws Exception
     */
    public function goodbye()
    {
        $this->write($this->packer->pack(0x02));
        $this->connection->disconnect();
    }
}

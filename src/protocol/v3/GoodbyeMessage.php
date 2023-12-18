<?php

namespace Bolt\protocol\v3;

use Bolt\enum\ServerState;
use Bolt\error\BoltException;

trait GoodbyeMessage
{
    /**
     * Send GOODBYE message
     * The GOODBYE message notifies the server that the connection is terminating gracefully.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-goodbye
     * @throws BoltException
     */
    public function goodbye(): void
    {
        $this->write($this->packer->pack(0x02));
        $this->connection->disconnect();
        $this->serverState = ServerState::DEFUNCT;
    }
}

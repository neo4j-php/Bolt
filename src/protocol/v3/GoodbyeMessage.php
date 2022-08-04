<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\ServerState;
use Exception;

trait GoodbyeMessage
{
    /**
     * Send GOODBYE message
     * The GOODBYE message notifies the server that the connection is terminating gracefully.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-goodbye
     * @throws Exception
     */
    public function goodbye()
    {
        $this->write($this->packer->pack(0x02));
        $this->connection->disconnect();
        $this->serverState->set(ServerState::DEFUNCT);
    }
}

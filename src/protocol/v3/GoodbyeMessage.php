<?php

namespace Bolt\protocol\v3;

use Bolt\helpers\ServerState;
use Exception;

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
        $this->serverState->set(ServerState::DEFUNCT);
    }
}

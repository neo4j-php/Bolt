<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\protocol\{V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-commit
     * @throws BoltException
     */
    public function commit(): V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->write($this->packer->pack(0x12));
        $this->pipelinedMessages[] = Message::COMMIT;
        return $this;
    }
}

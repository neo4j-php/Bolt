<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\protocol\{Response, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait RollbackMessage
{
    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-rollback
     * @throws BoltException
     */
    public function rollback(): V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->write($this->packer->pack(0x13));
        $this->pipelinedMessages[] = Message::ROLLBACK;
        return $this;
    }

    /**
     * Read ROLLBACK response
     * @return iterable
     * @throws BoltException
     */
    protected function _rollback(): iterable
    {
        $this->openStreams = 0;
        $content = $this->read($signature);
        yield new Response(Message::ROLLBACK, $signature, $content);
    }
}

<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\Message;
use Bolt\error\BoltException;
use Bolt\protocol\{Response, V5_1, V5_2, V5_3, V5_4};

trait LogonMessage
{
    /**
     * Send LOGON message
     * A LOGON message carries an authentication request.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-logon
     * @throws BoltException
     */
    public function logon(array $auth): V5_1|V5_2|V5_3|V5_4
    {
        $this->write($this->packer->pack(0x6A, (object)$auth));
        $this->pipelinedMessages[] = __FUNCTION__;
        return $this;
    }

    /**
     * Read LOGON response
     * @throws BoltException
     */
    public function _logon(): iterable
    {
        $content = $this->read($signature);
        yield new Response(Message::LOGON, $signature, $content);
    }
}

<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\protocol\{Response, V1, V2};
use Bolt\error\BoltException;

trait InitMessage
{
    /**
     * Send INIT message
     * The INIT message is a request for the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-init
     * @throws BoltException
     */
    public function init(string $userAgent, array $authToken): V1|V2
    {
        $this->write($this->packer->pack(0x01, $userAgent, $authToken));
        $this->pipelinedMessages[] = __FUNCTION__;
        return $this;
    }

    /**
     * Read INIT response
     * @throws BoltException
     */
    public function _init(): iterable
    {
        $content = $this->read($signature);
        yield new Response(Message::INIT, $signature, $content);
    }
}

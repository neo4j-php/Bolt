<?php

namespace Bolt\protocol\v4;

use Bolt\enum\{Message, Signature};
use Bolt\protocol\{Response, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait PullMessage
{
    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param array $extra [n::Integer, qid::Integer]
     * @throws BoltException
     */
    public function pull(array $extra = []): V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;
        $this->write($this->packer->pack(0x3F, $extra));
        $this->pipelinedMessages[] = Message::PULL;
        return $this;
    }

    /**
     * Read PULL responses
     * @return iterable
     * @throws BoltException
     */
    protected function _pull(): iterable
    {
        do {
            $content = $this->read($signature);
            if (!($content['has_more'] ?? false) && $this->openStreams) {
                if ($signature === Signature::SUCCESS)
                    $this->openStreams--;
                elseif ($signature === Signature::FAILURE)
                    $this->openStreams = 0;
            }
            yield new Response(Message::PULL, $signature, $content);
        } while ($signature == Signature::RECORD);
    }
}

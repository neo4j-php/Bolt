<?php

namespace Bolt\protocol\ServerState\V4_0;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\Signatures;

class TxStreaming extends \Bolt\protocol\ServerState\V3\TxStreaming
{
    private int $openResults = 1;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if ($message === Signatures::RUN && $response === Signatures::SUCCESS) {
            ++$this->openResults;
        }

        if (
            ($message === Signatures::PULL || $message === Signatures::DISCARD) &&
            $response === Signatures::SUCCESS
        ) {
            if ($data['has_more'] ?? false) {
                return ServerStates::TX_STREAMING;
            }

            --$this->openResults;
            if ($this->openResults === 0) {
                return ServerStates::TX_READY;
            }

            return ServerStates::TX_STREAMING;
        }

        return parent::transitionFromMessage($message, $response);
    }
}
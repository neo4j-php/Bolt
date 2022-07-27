<?php

namespace Bolt\protocol\ServerState\V4_0;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\Signatures;

class Streaming extends \Bolt\protocol\ServerState\V1\Streaming
{
    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            ($message === Signatures::PULL || $message === Signatures::DISCARD) &&
            $response === Signatures::SUCCESS &&
            ($data['has_more'] ?? false)
        ) {
            return ServerStates::STREAMING;
        }

        return parent::transitionFromMessage($message, $response);
    }
}
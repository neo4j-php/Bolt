<?php

namespace Bolt\protocol\ServerState\V4_3;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\Signatures;

class Ready extends \Bolt\protocol\ServerState\V3\Ready
{
    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            $message === Signatures::ROUTE &&
            $transition = $this->basicTransition($response, ServerStates::READY, ServerStates::FAILED)
        ) {
            return $transition;
        }

        return parent::transitionFromMessage($message, $response);
    }
}
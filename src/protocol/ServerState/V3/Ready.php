<?php

namespace Bolt\protocol\ServerState\V3;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\Signatures;

class Ready extends \Bolt\protocol\ServerState\V1\Ready
{
    use HandlesGoodbye;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            $message === Signatures::BEGIN &&
            $transition = $this->basicTransition($response, ServerStates::TX_READY, ServerStates::FAILED)
        ) {
            return $transition;
        }

        return $this->handleGoodbyeIfPossible($message, $response) ??
               parent::transitionFromMessage($message, $response);
    }
}
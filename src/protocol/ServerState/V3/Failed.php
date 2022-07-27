<?php

namespace Bolt\protocol\ServerState\V3;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\AcceptsBothSignals;
use Bolt\protocol\Signatures;

class Failed extends AServerState
{
    use HandlesGoodbye;
    use AcceptsBothSignals;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (in_array($message, [Signatures::RUN, Signatures::PULL_ALL, Signatures::DISCARD_ALL], true)) {
            return ServerStates::FAILED;
        }

        return $this->handleGoodbyeIfPossible($message, $response) ??
               ServerStates::UNKNOWN;
    }

    public function getName(): string
    {
        return 'CONNECTED';
    }
}
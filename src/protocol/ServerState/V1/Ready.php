<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\AcceptsBothSignals;
use Bolt\protocol\Signatures;

/**
 * The READY state can handle the request message RUN and receive a query.
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---ready
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---ready
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---ready
 */
class Ready extends AServerState
{
    use AcceptsBothSignals;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            ($message === Signatures::RUN) &&
            $transition = $this->basicTransition($response, ServerStates::STREAMING, ServerStates::FAILED)
        ) {
            return $transition;
        }

        return ServerStates::UNKNOWN;
    }

    public function getName(): string
    {
        return 'READY';
    }
}
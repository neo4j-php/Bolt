<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\AcceptsBothSignals;
use Bolt\protocol\Signatures;
/**
 * This state occurs between the server receiving the jump-ahead <INTERRUPT> and the queued RESET message, (the RESET message triggers an <INTERRUPT>). Most incoming messages are ignored when the server are in an INTERRUPTED state, with the exception of the RESET that allows transition back to READY.
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---interrupted
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---interrupted
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---interrupted
 */
class Interrupted extends AServerState
{
    use AcceptsBothSignals;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            $message === Signatures::RESET &&
            $transition = $this->basicTransition($response, ServerStates::READY, ServerStates::DEFUNCT)
        ) {
            return $transition;
        }

        return ServerStates::INTERRUPTED;
    }

    public function getName(): string
    {
        return 'INTERRUPTED';
    }
}
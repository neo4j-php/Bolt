<?php

namespace Bolt\protocol\ServerState\V3;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\AcceptsBothSignals;
use Bolt\protocol\Signatures;

/**
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---tx_ready
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---tx_ready
 */
class TxReady extends AServerState
{
    use AcceptsBothSignals;
    use HandlesGoodbye;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            ($message === Signatures::COMMIT || $message === Signatures::ROLLBACK) &&
            $transition = $this->basicTransition($response, ServerStates::READY, ServerStates::FAILED)
        ) {
            return $transition;
        }

        if (
            $message === Signatures::RUN &&
            $transition = $this->basicTransition($response, ServerStates::TX_STREAMING, ServerStates::FAILED)
        ) {
            return $transition;
        }

        return $this->handleGoodbyeIfPossible($message, $response) ?? ServerStates::UNKNOWN;
    }

    public function getName(): string
    {
        return 'TX_READY';
    }
}
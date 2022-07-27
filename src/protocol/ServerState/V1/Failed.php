<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\AcceptsBothSignals;
use Bolt\protocol\Signatures;

/**
 * When FAILED, a connection is in a temporarily unusable state. This is generally as the result of encountering a recoverable error. No more work will be processed until the failure has been acknowledged by ACK_FAILURE (V1 Only) or until the connection has been RESET
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---failed
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---failed
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---failed
 */
class Failed extends AServerState
{
    use AcceptsBothSignals;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if ($response === Signatures::IGNORED) {
            if ($message === Signatures::RUN || $message === Signatures::PULL_ALL) {
                return ServerStates::FAILED;
            }

            if ($message === Signatures::DISCARD_ALL) {
                return ServerStates::INTERRUPTED;
            }
        }

        if (
            $message === Signatures::ACK_FAILURE &&
            $transition = $this->basicTransition($response, ServerStates::READY, ServerStates::DEFUNCT)
        ) {
            return $transition;
        }

        return ServerStates::UNKNOWN;
    }

    public function getName(): string
    {
        return 'FAILED';
    }
}
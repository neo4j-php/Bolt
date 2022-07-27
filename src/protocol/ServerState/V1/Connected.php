<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\Signatures;

/**
 * State representing when a protocol connection has been established and a handshake has been completed successfully, but not yet authenticated.
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---connected
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---connected
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---connected
 */
class Connected extends AServerState
{
    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if (
            $message === Signatures::INIT &&
            $transition = $this->basicTransition($response, ServerStates::READY, ServerStates::DEFUNCT)
        ) {
            return $transition;
        }

        return ServerStates::UNKNOWN;
    }

    public function transitionFromSignal(int $signal): int
    {
        if ($signal === ServerStateSignal::DISCONNECT) {
            return ServerStates::DEFUNCT;
        }

        return ServerStates::CONNECTED;
    }

    public function getName(): string
    {
        return 'CONNECTED';
    }
}
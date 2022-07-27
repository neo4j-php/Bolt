<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;

/**
 * A logical state when there isn't an established socket connection.
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---disconnected
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---disconnected
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---disconnected
 */
class Disconnected extends AServerState
{
    public function transitionAfterHandshake(bool $success): int
    {
        if ($success) {
            return ServerStates::CONNECTED;
        }

        return ServerStates::DEFUNCT;
    }

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        // A disconnected state is a logical state and does nothing when a message is sent to it
        // Instead, the handshake protocol must manually advance the state to the
        // CONNECTED state
        return ServerStates::UNKNOWN;
    }

    public function transitionFromSignal(int $signal): int
    {
        return ServerStates::DISCONNECTED;
    }

    public function getName(): string
    {
        return 'DISCONNECTED';
    }
}
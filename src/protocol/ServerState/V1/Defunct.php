<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;

/**
 * This is not strictly a connection state, but is instead a logical state that exists after a connection has been closed. When DEFUNCT, a connection is permanently not usable.
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---defunct
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---defunct
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---defunct
 */
class Defunct extends AServerState
{
    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        return ServerStates::DEFUNCT;
    }

    public function transitionFromSignal(int $signal): int
    {
        return ServerStates::DEFUNCT;
    }

    public function getName(): string
    {
        return 'DEFUNCT';
    }
}
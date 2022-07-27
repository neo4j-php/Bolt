<?php

namespace Bolt\protocol\ServerState\V1;

use Bolt\protocol\ServerState\AServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\AcceptsBothSignals;
use Bolt\protocol\Signatures;

/**
 * When STREAMING, a result is available for streaming from server to client. This result must be fully consumed or discarded by a client before the server can re-enter the READY state and allow any further queries to be executed.
 *
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-1.html#server-state---streaming
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-3.html#server-state---streaming
 * @link https://7687.org/bolt/bolt-protocol-server-state-specification-4.html#server-state---streaming
 */
class Streaming extends AServerState
{
    use AcceptsBothSignals;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        if ($message === Signatures::PULL_ALL && $response === Signatures::RECORD) {
            return ServerStates::STREAMING;
        }

        if (
            ($message === Signatures::PULL_ALL || $message === Signatures::DISCARD_ALL) &&
            $transition = $this->basicTransition($response, ServerStates::READY, ServerStates::FAILED)
        ) {
            return $transition;
        }

        return ServerStates::UNKNOWN;
    }

    public function getName(): string
    {
        return 'STREAMING';
    }
}
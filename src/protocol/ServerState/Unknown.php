<?php

namespace Bolt\protocol\ServerState;

class Unknown extends AServerState
{
    use AcceptsBothSignals;

    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int
    {
        return ServerStates::UNKNOWN;
    }

    public function getName(): string
    {
        return 'UNKNOWN';
    }
}
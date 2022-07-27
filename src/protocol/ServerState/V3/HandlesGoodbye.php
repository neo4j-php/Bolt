<?php

namespace Bolt\protocol\ServerState\V3;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\Signatures;

trait HandlesGoodbye
{
    public function handleGoodbyeIfPossible(int $message, ?int $response): ?int
    {
        if ($message === Signatures::GOODBYE && $response === null) {
            return ServerStates::DEFUNCT;
        }

        return null;
    }
}
<?php

namespace Bolt\protocol\ServerState;

use Bolt\protocol\Signatures;

abstract class AServerState implements IServerState
{
    public function transitionAfterHandshake(bool $success): int
    {
        return ServerStates::UNKNOWN;
    }

    protected function basicTransition(?int $response, int $onSuccessState, int $onFailureState): ?int
    {
        if ($response === Signatures::SUCCESS) {
            return $onSuccessState;
        }

        if ($response === Signatures::FAILURE) {
            return $onFailureState;
        }

        return null;
    }
}
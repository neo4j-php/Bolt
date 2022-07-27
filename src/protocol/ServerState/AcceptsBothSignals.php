<?php

namespace Bolt\protocol\ServerState;

trait AcceptsBothSignals
{
    public function transitionFromSignal(int $signal): int
    {
        if ($signal === ServerStateSignal::INTERRUPT) {
            return ServerStates::INTERRUPTED;
        }

        return ServerStates::DEFUNCT;
    }
}
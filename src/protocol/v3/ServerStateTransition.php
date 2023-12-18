<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\enum\ServerState;
use Bolt\enum\Signature;

trait ServerStateTransition
{
    protected array $serverStateTransition = [
        [ServerState::CONNECTED, Message::HELLO, Signature::SUCCESS, ServerState::READY],
        [ServerState::CONNECTED, Message::HELLO, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::READY, Message::RUN, Signature::SUCCESS, ServerState::STREAMING],
        [ServerState::READY, Message::RUN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::READY, Message::BEGIN, Signature::SUCCESS, ServerState::TX_READY],
        [ServerState::READY, Message::BEGIN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::READY, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::READY, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::STREAMING, Message::PULL_ALL, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::PULL_ALL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::STREAMING, Message::DISCARD_ALL, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::DISCARD_ALL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::STREAMING, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::TX_READY, Message::RUN, Signature::SUCCESS, ServerState::TX_STREAMING],
        [ServerState::TX_READY, Message::RUN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_READY, Message::COMMIT, Signature::SUCCESS, ServerState::READY],
        [ServerState::TX_READY, Message::COMMIT, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_READY, Message::ROLLBACK, Signature::SUCCESS, ServerState::READY],
        [ServerState::TX_READY, Message::ROLLBACK, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_READY, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::TX_READY, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::TX_STREAMING, Message::PULL_ALL, Signature::SUCCESS, ServerState::TX_STREAMING],
        [ServerState::TX_STREAMING, Message::PULL_ALL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_STREAMING, Message::DISCARD_ALL, Signature::SUCCESS, ServerState::TX_STREAMING],
        [ServerState::TX_STREAMING, Message::DISCARD_ALL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_STREAMING, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::TX_STREAMING, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::FAILED, Message::RUN, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::PULL_ALL, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::DISCARD_ALL, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::FAILED, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::INTERRUPTED, Message::RUN, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::PULL_ALL, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::DISCARD_ALL, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::BEGIN, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::COMMIT, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::ROLLBACK, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::INTERRUPTED, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
    ];
}

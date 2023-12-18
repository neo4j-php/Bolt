<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\enum\ServerState;
use Bolt\enum\Signature;

trait ServerStateTransition
{
    protected array $serverStateTransition = [
        [ServerState::CONNECTED, Message::INIT, Signature::SUCCESS, ServerState::READY],
        [ServerState::CONNECTED, Message::INIT, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::READY, Message::RUN, Signature::SUCCESS, ServerState::STREAMING],
        [ServerState::READY, Message::RUN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::READY, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::READY, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::STREAMING, Message::PULL_ALL, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::PULL_ALL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::STREAMING, Message::DISCARD_ALL, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::DISCARD_ALL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::STREAMING, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::FAILED, Message::RUN, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::PULL_ALL, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::DISCARD_ALL, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::FAILED, Message::ACK_FAILURE, Signature::SUCCESS, ServerState::READY],
        [ServerState::FAILED, Message::ACK_FAILURE, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::FAILED, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::FAILED, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::INTERRUPTED, Message::RUN, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::PULL_ALL, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::DISCARD_ALL, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::ACK_FAILURE, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::INTERRUPTED, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
    ];
}

<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\Message;
use Bolt\enum\ServerState;
use Bolt\enum\Signature;

trait ServerStateTransition
{
    protected array $serverStateTransition = [
        [ServerState::NEGOTIATION, Message::HELLO, Signature::SUCCESS, ServerState::AUTHENTICATION],
        [ServerState::NEGOTIATION, Message::HELLO, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::AUTHENTICATION, Message::LOGON, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::AUTHENTICATION, Message::LOGON, Signature::SUCCESS, ServerState::READY],
        [ServerState::READY, Message::LOGOFF, Signature::FAILURE, ServerState::FAILED],
        [ServerState::READY, Message::LOGOFF, Signature::SUCCESS, ServerState::AUTHENTICATION],
        [ServerState::READY, Message::RUN, Signature::SUCCESS, ServerState::STREAMING],
        [ServerState::READY, Message::RUN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::READY, Message::BEGIN, Signature::SUCCESS, ServerState::TX_READY],
        [ServerState::READY, Message::BEGIN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::READY, Message::ROUTE, Signature::SUCCESS, ServerState::READY],
        [ServerState::READY, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::READY, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::STREAMING, Message::PULL, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::PULL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::STREAMING, Message::DISCARD, Signature::SUCCESS, ServerState::READY],
        [ServerState::STREAMING, Message::DISCARD, Signature::FAILURE, ServerState::FAILED],
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
        [ServerState::TX_STREAMING, Message::RUN, Signature::SUCCESS, ServerState::TX_STREAMING],
        [ServerState::TX_STREAMING, Message::RUN, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_STREAMING, Message::PULL, Signature::SUCCESS, ServerState::TX_READY],
        [ServerState::TX_STREAMING, Message::PULL, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_STREAMING, Message::DISCARD, Signature::SUCCESS, ServerState::TX_READY],
        [ServerState::TX_STREAMING, Message::DISCARD, Signature::FAILURE, ServerState::FAILED],
        [ServerState::TX_STREAMING, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::TX_STREAMING, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::FAILED, Message::RUN, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::PULL, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::DISCARD, Signature::IGNORED, ServerState::FAILED],
        [ServerState::FAILED, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::FAILED, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
        [ServerState::INTERRUPTED, Message::RUN, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::PULL, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::DISCARD, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::BEGIN, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::COMMIT, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::ROLLBACK, Signature::IGNORED, ServerState::INTERRUPTED],
        [ServerState::INTERRUPTED, Message::RESET, Signature::SUCCESS, ServerState::READY],
        [ServerState::INTERRUPTED, Message::RESET, Signature::FAILURE, ServerState::DEFUNCT],
    ];
}

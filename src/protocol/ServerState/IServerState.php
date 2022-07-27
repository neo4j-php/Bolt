<?php

namespace Bolt\protocol\ServerState;

interface IServerState
{
    /**
     * Create the server state transition given the provided request and response signature.
     *
     * @param int $message  The request signature.
     * @param int|null $response The response signature.
     *
     * @return int  The next expected server state.
     */
    public function transitionFromMessage(int $message, ?int $response = null, array $data = []): int;

    /**
     * Create the server state transition when a given signal occurs.
     *
     * @param int $signal The signal that occurs.
     *
     * @return int  The next expected server state.
     */
    public function transitionFromSignal(int $signal): int;

    public function transitionAfterHandshake(bool $success): int;

    public function getName(): string;
}
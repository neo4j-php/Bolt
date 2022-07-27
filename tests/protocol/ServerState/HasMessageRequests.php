<?php

namespace Bolt\tests\protocol\ServerState;

use Bolt\protocol\ServerState\IServerState;
use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\Signatures;
use function array_search;
use function json_encode;

trait HasMessageRequests
{
    protected array $messages = [
        'INIT/HELLO' => Signatures::INIT,
        'GOODBYE' => Signatures::GOODBYE,
        'ACK_FAILURE' => Signatures::ACK_FAILURE,
        'RESET' => Signatures::RESET,
        'RUN' => Signatures::RUN,
        'BEGIN' => Signatures::BEGIN,
        'COMMIT' => Signatures::COMMIT,
        'ROLLBACK' => Signatures::ROLLBACK,
        'DISCARD(_ALL)' => Signatures::DISCARD,
        'PULL(_ALL)' => Signatures::PULL_ALL,
        'ROUTE' => Signatures::ROUTE,
    ];

    protected array $responses = [
        'SUCCESS' => Signatures::SUCCESS,
        'RECORD' => Signatures::RECORD,
        'IGNORED' => Signatures::IGNORED,
        'FAILURE' => Signatures::FAILURE,
        'NULL' => null
    ];

    private array $signals = [
        '<INTERRUPT>' => ServerStateSignal::INTERRUPT,
        '<DISCONNECT>' => ServerStateSignal::DISCONNECT,
    ];

    protected IServerState $state;

    /**
     * @param array<array{message: int, response: int, state: int}> $overrides
     *
     * @return array
     */
    public function generateMessageData(array $overrides): array
    {
        $tbr = [];
        foreach ($this->messages as $messageName => $message) {
            foreach ($this->responses as $responseName => $response) {
                $tbr[$messageName . ' ' . $responseName] = [$message, $response, ServerStates::UNKNOWN, []];
            }
        }

        foreach ($overrides as $override) {
            $pos = array_search($override['message'], $this->messages, true);
            $pos .= ' ' . array_search($override['response'], $this->responses, true);
            if ($override['data'] ?? false) {
                $pos .= ' '.json_encode($override['data'], JSON_THROW_ON_ERROR);
            }

            $tbr[$pos] = [$override['message'], $override['response'], $override['state'], $override['data'] ?? []];
        }

        return $tbr;
    }

    /**
     * @param int $currentState
     * @param array<array{signal: int, state: int}> $overrides
     *
     * @return array
     */
    public function generateSignalData(int $currentState, array $overrides): array
    {
        $tbr = [];
        foreach ($this->signals as $signalName => $signal) {
            $tbr[$signalName] = [$signal, $currentState];
        }

        foreach ($overrides as $override) {
            $pos = array_search($override['signal'], $this->signals, true);

            $tbr[$pos] = [$override['signal'],  $override['state']];
        }

        return $tbr;
    }

    /**
     * @dataProvider messageMaps
     */
    public function testTransitionFromMessage(int $message, ?int $response, int $expected, array $data): void
    {
        $this->assertEquals($expected, $this->state->transitionFromMessage($message, $response, $data));
    }

    /**
     * @dataProvider signalMaps
     */
    public function testTransitionFromSignal(int $signal, ?int $expected): void
    {
        $this->assertEquals($expected, $this->state->transitionFromSignal($signal));
    }

    abstract public function messageMaps(): array;

    abstract public function signalMaps(): array;

    public function testAfterHandshake(): void
    {
        $this->assertEquals(ServerStates::UNKNOWN, $this->state->transitionAfterHandshake(true));
        $this->assertEquals(ServerStates::UNKNOWN, $this->state->transitionAfterHandshake(false));
    }
}
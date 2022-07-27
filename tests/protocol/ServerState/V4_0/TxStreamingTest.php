<?php

namespace Bolt\tests\protocol\ServerState\V4_0;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\V4_0\TxStreaming;
use Bolt\protocol\ServerState\V4_3\Ready;
use Bolt\protocol\Signatures;

class TxStreamingTest extends \Bolt\tests\protocol\ServerState\V3\TxStreamingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new TxStreaming();
    }

    public function testFirstRun(): void
    {
        $state = $this->state->transitionFromMessage(Signatures::RUN, Signatures::SUCCESS);
        $this->assertEquals(ServerStates::TX_STREAMING, $state);

        $state = $this->state->transitionFromMessage(Signatures::PULL, Signatures::SUCCESS, ['has_more' => true]);
        $this->assertEquals(ServerStates::TX_STREAMING, $state);

        $state = $this->state->transitionFromMessage(Signatures::PULL, Signatures::SUCCESS, ['has_more' => false]);
        $this->assertEquals(ServerStates::TX_STREAMING, $state);

        $state = $this->state->transitionFromMessage(Signatures::RUN, Signatures::SUCCESS);
        $this->assertEquals(ServerStates::TX_STREAMING, $state);


        $state = $this->state->transitionFromMessage(Signatures::PULL, Signatures::SUCCESS, ['has_more' => false]);
        $this->assertEquals(ServerStates::TX_STREAMING, $state);


        $state = $this->state->transitionFromMessage(Signatures::PULL, Signatures::SUCCESS, ['has_more' => false]);
        $this->assertEquals(ServerStates::TX_READY, $state);
    }

    public function generateMessageData(array $overrides): array
    {
        array_push(
            $overrides,
            [
                'message'  => Signatures::PULL,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::TX_STREAMING,
                'data' => ['has_more' => true],
            ],
            [
                'message'  => Signatures::PULL,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::TX_READY,
                'data' => ['has_more' => false],
            ],
            [
                'message'  => Signatures::DISCARD,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::TX_STREAMING,
                'data' => ['has_more' => true],
            ],
            [
                'message'  => Signatures::DISCARD,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::TX_READY,
                'data' => ['has_more' => false],
            ]
        );

        return parent::generateMessageData($overrides);
    }
}
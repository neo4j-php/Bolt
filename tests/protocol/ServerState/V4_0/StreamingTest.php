<?php

namespace Bolt\tests\protocol\ServerState\V4_0;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\V4_0\Streaming;
use Bolt\protocol\Signatures;

class StreamingTest extends \Bolt\tests\protocol\ServerState\V1\StreamingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Streaming();
    }

    public function generateMessageData(array $overrides): array
    {
        array_push(
            $overrides,
            [
                'message'  => Signatures::PULL,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::STREAMING,
                'data' => ['has_more' => true],
            ],
            [
                'message'  => Signatures::PULL,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::READY,
                'data' => ['has_more' => false],
            ],
            [
                'message'  => Signatures::DISCARD,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::STREAMING,
                'data' => ['has_more' => true],
            ],
            [
                'message'  => Signatures::DISCARD,
                'response' => Signatures::SUCCESS,
                'state'    => ServerStates::READY,
                'data' => ['has_more' => false],
            ]
        );

        return parent::generateMessageData($overrides);
    }
}
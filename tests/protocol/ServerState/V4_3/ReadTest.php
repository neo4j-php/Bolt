<?php

namespace Bolt\tests\protocol\ServerState\V4_3;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\V4_3\Ready;
use Bolt\protocol\Signatures;
use Bolt\tests\protocol\ServerState\V3\ReadyTest;

class ReadTest extends ReadyTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Ready();
    }

    public function generateMessageData(array $overrides): array
    {
        $overrides[] = ['message' => Signatures::ROUTE, 'response' => Signatures::SUCCESS, 'state' => ServerStates::READY];
        $overrides[] = ['message' => Signatures::ROUTE, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED];

        return parent::generateMessageData($overrides);
    }
}
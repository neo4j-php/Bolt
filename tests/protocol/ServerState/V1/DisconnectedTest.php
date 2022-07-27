<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V1\Disconnected;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class DisconnectedTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Disconnected();
    }

    public function testAfterHandshake(): void
    {
        $this->assertEquals(ServerStates::CONNECTED, $this->state->transitionAfterHandshake(true));
        $this->assertEquals(ServerStates::DEFUNCT, $this->state->transitionAfterHandshake(false));
    }

    public function messageMaps(): array
    {
        return $this->generateMessageData([]);
    }

    public function signalMaps(): array
    {
        return $this->generateSignalData(ServerStates::CONNECTED, [
            ['signal' => ServerStateSignal::DISCONNECT, 'state' => ServerStates::DISCONNECTED],
            ['signal' => ServerStateSignal::INTERRUPT, 'state' => ServerStates::DISCONNECTED]
        ]);
    }
}

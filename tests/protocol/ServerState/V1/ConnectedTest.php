<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V1\Connected;
use Bolt\protocol\Signatures;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class ConnectedTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Connected();
    }

    public function messageMaps(): array
    {
        return $this->generateMessageData([
            ['message' => Signatures::INIT, 'response' => Signatures::SUCCESS, 'state' => ServerStates::READY],
            ['message' => Signatures::INIT, 'response' => Signatures::FAILURE, 'state' => ServerStates::DEFUNCT],
        ]);
    }

    public function signalMaps(): array
    {
        return $this->generateSignalData(ServerStates::CONNECTED, [
            ['signal' => ServerStateSignal::DISCONNECT, 'state' => ServerStates::DEFUNCT]
        ]);
    }
}

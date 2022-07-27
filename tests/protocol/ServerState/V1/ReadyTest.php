<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V1\Connected;
use Bolt\protocol\ServerState\V1\Ready;
use Bolt\protocol\Signatures;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class ReadyTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Ready();
    }

    public function messageMaps(): array
    {
        return $this->generateMessageData([
            ['message' => Signatures::RUN, 'response' => Signatures::SUCCESS, 'state' => ServerStates::STREAMING],
            ['message' => Signatures::RUN, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED],
            ['message' => Signatures::RESET, 'response' => null, 'state' => ServerStates::UNKNOWN],
        ]);
    }

    public function signalMaps(): array
    {
        return $this->generateSignalData(ServerStates::CONNECTED, [
            ['signal' => ServerStateSignal::DISCONNECT, 'state' => ServerStates::DEFUNCT],
            ['signal' => ServerStateSignal::INTERRUPT, 'state' => ServerStates::INTERRUPTED]
        ]);
    }
}

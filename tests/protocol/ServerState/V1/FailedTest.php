<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V1\Connected;
use Bolt\protocol\ServerState\V1\Failed;
use Bolt\protocol\ServerState\V1\Ready;
use Bolt\protocol\Signatures;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class FailedTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Failed();
    }

    public function messageMaps(): array
    {
        return $this->generateMessageData([
            ['message' => Signatures::RUN, 'response' => Signatures::IGNORED, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::IGNORED, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::IGNORED, 'state' => ServerStates::INTERRUPTED],
            ['message' => Signatures::ACK_FAILURE, 'response' => Signatures::SUCCESS, 'state' => ServerStates::READY],
            ['message' => Signatures::ACK_FAILURE, 'response' => Signatures::FAILURE, 'state' => ServerStates::DEFUNCT],
            ['message' => Signatures::RESET, 'response' => Signatures::IGNORED, 'state' => ServerStates::UNKNOWN]
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

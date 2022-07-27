<?php

namespace Bolt\tests\protocol\ServerState\V3;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V3\Failed;
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
            ['message' => Signatures::RUN, 'response' => null, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => null, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => null, 'state' => ServerStates::FAILED],
            ['message' => Signatures::RUN, 'response' => Signatures::SUCCESS, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::SUCCESS, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::SUCCESS, 'state' => ServerStates::FAILED],
            ['message' => Signatures::RUN, 'response' => Signatures::IGNORED, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::IGNORED, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::IGNORED, 'state' => ServerStates::FAILED],
            ['message' => Signatures::RUN, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED],
            ['message' => Signatures::RUN, 'response' => Signatures::RECORD, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::RECORD, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::RECORD, 'state' => ServerStates::FAILED],
            ['message' => Signatures::GOODBYE, 'response' => null, 'state' => ServerStates::DEFUNCT],
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

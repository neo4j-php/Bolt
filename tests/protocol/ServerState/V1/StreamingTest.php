<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V1\Ready;
use Bolt\protocol\ServerState\V1\Streaming;
use Bolt\protocol\Signatures;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class StreamingTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Streaming();
    }

    public function messageMaps(): array
    {
        return $this->generateMessageData([
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::SUCCESS, 'state' => ServerStates::READY],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::SUCCESS, 'state' => ServerStates::READY],
            ['message' => Signatures::DISCARD_ALL, 'response' => Signatures::FAILURE, 'state' => ServerStates::FAILED],
            ['message' => Signatures::PULL_ALL, 'response' => Signatures::RECORD, 'state' => ServerStates::STREAMING],
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

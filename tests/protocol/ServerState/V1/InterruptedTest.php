<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\ServerStateSignal;
use Bolt\protocol\ServerState\V1\Interrupted;
use Bolt\protocol\Signatures;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class InterruptedTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Interrupted();
    }

    public function messageMaps(): array
    {
        $tbr = [];
        foreach ($this->messages as $messageString => $message) {
            foreach ($this->responses as $responseString => $response) {
                $tbr[$messageString . ' ' . $responseString] = [$message, $response, ServerStates::INTERRUPTED, []];
            }
        }

        $tbr['RESET SUCCESS'] = [Signatures::RESET, Signatures::SUCCESS, ServerStates::READY, []];
        $tbr['RESET FAILURE'] = [Signatures::RESET, Signatures::FAILURE, ServerStates::DEFUNCT, []];

        return $tbr;
    }

    public function signalMaps(): array
    {
        return $this->generateSignalData(ServerStates::INTERRUPTED, [
            ['signal' => ServerStateSignal::INTERRUPT, 'state' => ServerStates::INTERRUPTED],
            ['signal' => ServerStateSignal::DISCONNECT, 'state' => ServerStates::DEFUNCT],
        ]);
    }
}
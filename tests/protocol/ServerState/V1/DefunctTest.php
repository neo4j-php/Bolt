<?php

namespace Bolt\tests\protocol\ServerState\V1;

use Bolt\protocol\ServerState\ServerStates;
use Bolt\protocol\ServerState\V1\Defunct;
use Bolt\tests\protocol\ServerState\HasMessageRequests;
use PHPUnit\Framework\TestCase;

class DefunctTest extends TestCase
{
    use HasMessageRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->state = new Defunct();
    }

    public function messageMaps(): array
    {
        $tbr = [];
        foreach ($this->messages as $messageString => $message) {
            foreach ($this->responses as $responseString => $response) {
                $tbr[$messageString . ' ' . $responseString] = [$message, $response, ServerStates::DEFUNCT, []];
            }
        }

        return $tbr;
    }

    public function signalMaps(): array
    {
        return $this->generateSignalData(ServerStates::DEFUNCT, []);
    }
}
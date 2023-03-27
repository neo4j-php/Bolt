<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\protocol\{AProtocol, Response, V4_4, V5, V5_1};
use Bolt\tests\packstream\v1\generators\RandomDataGenerator;

/**
 * Class PerformanceTest
 * @author Ghlen Nagels
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class PerformanceTest extends ATest
{
    use CreatesSockets;

    public function test30kRecords(): void
    {
        $amount = 30000;

        $conn = $this->createStreamSocket();

        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        //prevent multiple runs at once
        while (true) {
            $runs = $this->basicRun($protocol, 'MATCH (n:Test50k) RETURN count(n)')[0];

            if ($runs > 0) {
                sleep(60);
            } else {
                $this->basicRun($protocol, 'CREATE (n:Test50k)');
                break;
            }
        }

        $generator = new RandomDataGenerator($amount);
        $response = $protocol
            ->run('UNWIND $x as x RETURN x', ['x' => $generator])
            ->getResponse();
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());

        $iterator = $protocol->pull()->getResponses();
        $count = 0;
        /** @var Response $response */
        foreach ($iterator as $response) {
            if ($response->getSignature() === Response::SIGNATURE_RECORD)
                $count++;
        }

        $this->basicRun($protocol, 'MATCH (n:Test50k) DELETE n');
        $this->assertEquals($amount, $count);
    }
}

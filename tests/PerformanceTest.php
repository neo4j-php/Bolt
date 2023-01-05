<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\helpers\Auth;
use Bolt\protocol\{
    AProtocol,
    Response,
    V1,
    V2,
    V3,
    V4,
    V4_1,
    V4_2,
    V4_3,
    V4_4,
    V5
};
use Bolt\tests\packstream\v1\generators\RandomDataGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class PerformanceTest
 * @author Ghlen Nagels
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class PerformanceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $GLOBALS['NEO_USER'] = getenv('GDB_USERNAME');
        $GLOBALS['NEO_PASS'] = getenv('GDB_PASSWORD');
        $host = getenv('GDB_HOST');
        if (!empty($host))
            $GLOBALS['NEO_HOST'] = $host;
        $port = getenv('GDB_PORT');
        if (!empty($port))
            $GLOBALS['NEO_PORT'] = $port;
    }

    public function test50KRecords(): void
    {
        $amount = 50000;

        $conn = new Socket($GLOBALS['NEO_HOST'] ?? 'localhost', $GLOBALS['NEO_PORT'] ?? 7687, 60);
        /** @var AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $this->assertEquals(\Bolt\protocol\Response::SIGNATURE_SUCCESS, $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

        $generator = new RandomDataGenerator($amount);
        $protocol
            ->run('UNWIND $x as x RETURN x', ['x' => $generator])
            ->getResponse();


        $iterator = $protocol->pull()->getResponses();
        $count = 0;
        /** @var Response $response */
        foreach ($iterator as $response) {
            if ($response->getSignature() === Response::SIGNATURE_RECORD)
                $count++;
        }

        $this->assertEquals($amount, $count);
    }
}

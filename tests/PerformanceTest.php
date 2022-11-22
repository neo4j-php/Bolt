<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\StreamSocket;
use Bolt\helpers\Auth;
use Bolt\protocol\{AProtocol, V4_3, V4_4, V5};
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
    public function test50KRecords(): void
    {
        $amount = 50000;

        $conn = new StreamSocket($GLOBALS['NEO_HOST'] ?? 'localhost', $GLOBALS['NEO_PORT'] ?? 7687, 60);
        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $this->assertEquals(\Bolt\protocol\Response::SIGNATURE_SUCCESS, $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

        $generator = new RandomDataGenerator($amount);
        $protocol
            ->run('UNWIND $x as x RETURN x', ['x' => $generator])
            ->getResponse();

        $count = 0;
        while (true) {
            $gen = $protocol
                ->pull(['n' => 1])
                ->getResponses();

            if ($gen->current()->getSignature() != \Bolt\protocol\Response::SIGNATURE_RECORD)
                $this->markTestIncomplete('Response does not contains record message');

            $gen->next();

            if ($gen->current()->getSignature() != \Bolt\protocol\Response::SIGNATURE_SUCCESS)
                $this->markTestIncomplete('Response does not contains success message');

            $count++;

            if ($gen->current()->getContent()['has_more'] ?? false)
                continue;
            else
                break;
        }

        $this->assertEquals($amount, $count);
    }
}

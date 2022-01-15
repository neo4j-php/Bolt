<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\connection\StreamSocket;
use Bolt\error\ConnectException;
use Bolt\error\MessageException;
use Bolt\helpers\Auth;
use Bolt\protocol\V4;
use Exception;
use PHPUnit\Framework\TestCase;
use function microtime;

final class StreamSocketTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testMillisecondTimeout(): void
    {
        $socket = new StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1.5);
        $bolt = new Bolt($socket);
        $protocol = $bolt->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
  MERGE (d:Day {day: i})
)');
        } catch (ConnectException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.5, $newTime - $time, 0.2);
        }

        self::assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testSecondsTimeout(): void
    {
        $socket = new StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1);
        $bolt = new Bolt($socket);
        $protocol = $bolt->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
  MERGE (d:Day {day: i})
)');
            $this->fail('No timeout error triggered');
        } catch (ConnectException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }
    }

    public function testTimeoutRecoverAndReset(): void
    {
        $streamSocket = new StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1);
        /** @var V4 $protocol */
        $protocol = (new Bolt($streamSocket))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
                MERGE (d:Day {day: i})
            )');
            $this->fail('No timeout error triggered');
        } catch (ConnectException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }

        $streamSocket->setTimeout(100.0);
        try {
            $protocol->reset();
        } catch (MessageException $e) {
            echo $e->getMessage();
            $protocol = (new Bolt($streamSocket))->build();
            $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        }
        $this->assertTrue(true);

        $streamSocket->setTimeout(1.0);

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
                MERGE (d:Day {day: i})
            )');
            $this->fail('No timeout error triggered');
        } catch (ConnectException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }
    }
}

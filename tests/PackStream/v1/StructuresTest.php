<?php

namespace Bolt\tests\PackStream\v1;

use Bolt\Bolt;
use Bolt\PackStream\Bytes;
use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
use Bolt\protocol\v1\structures\{
    Node,
    Relationship,
    UnboundRelationship,
    Path,
    Date,
    Time,
    LocalTime,
    DateTime,
    DateTimeZoneId,
    LocalDateTime,
    Duration,
    Point2D,
    Point3D
};
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * Class StructuresTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\v1\structures\Date
 * @covers \Bolt\protocol\v1\structures\DateTime
 * @covers \Bolt\protocol\v1\structures\DateTimeZoneId
 * @covers \Bolt\protocol\v1\structures\Duration
 * @covers \Bolt\protocol\v1\structures\LocalDateTime
 * @covers \Bolt\protocol\v1\structures\LocalTime
 * @covers \Bolt\protocol\v1\structures\Node
 * @covers \Bolt\protocol\v1\structures\Path
 * @covers \Bolt\protocol\v1\structures\Point2D
 * @covers \Bolt\protocol\v1\structures\Point3D
 * @covers \Bolt\protocol\v1\structures\Relationship
 * @covers \Bolt\protocol\v1\structures\Time
 * @covers \Bolt\protocol\v1\structures\UnboundRelationship
 *
 * @covers \Bolt\PackStream\v1\Packer
 * @covers \Bolt\PackStream\v1\Unpacker
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 */
class StructuresTest extends TestCase
{
    /**
     * How many iterations do for each date/time test
     * @var int
     */
    public static $iterations = 50;

    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

        return $protocol;
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     * @param int $timestamp
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testDate(int $timestamp, AProtocol $protocol)
    {
        $date = gmdate('Y-m-d', $timestamp);

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN date($date)', [
                    'date' => $date
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $dateStructure = $res[1]->getContent()[0];

        $this->assertInstanceOf(Date::class, $dateStructure);
        $this->assertEquals($date, (string)$dateStructure, 'unpack ' . $date . ' != ' . $dateStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($date)', [
                    'date' => $dateStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($date, $res[1]->getContent()[0], 'pack ' . $date . ' != ' . $res[1]->getContent()[0]);
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     * @param int $timestamp
     * @param string $timezone
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testDateTime(int $timestamp, string $timezone, AProtocol $protocol)
    {
        $timestamp .= '.' . rand(0, 9e5);
        $datetime = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
            ->format('Y-m-d\TH:i:s.uP');

        //unpack
        $res = iterator_to_array(
            $protocol->run('RETURN datetime($date)', [
                'date' => $datetime
            ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $dateTimeStructure = $res[1]->getContent()[0];

        $this->assertInstanceOf(DateTime::class, $dateTimeStructure);
        $this->assertEquals($datetime, (string)$dateTimeStructure, 'unpack ' . $datetime . ' != ' . $dateTimeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($date)', [
                    'date' => $dateTimeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        // neo4j returns fraction of seconds not padded with zeros ... zero timezone offset returns as Z
        $datetime = preg_replace(["/\.?0+(.\d{2}:\d{2})$/", "/\+00:00$/"], ['$1', 'Z'], $datetime);
        $this->assertEquals($datetime, $res[1]->getContent()[0], 'pack ' . $datetime . ' != ' . $res[1]->getContent()[0]);
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     * @param int $timestamp
     * @param string $timezone
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testDateTimeZoneId(int $timestamp, string $timezone, AProtocol $protocol)
    {
        try {
            $timestamp .= '.' . rand(0, 9e5);
            $datetime = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
                    ->format('Y-m-d\TH:i:s.u') . '[' . $timezone . ']';

            //unpack
            $res = iterator_to_array(
                $protocol
                    ->run('RETURN datetime($dt)', [
                        'dt' => $datetime
                    ], ['mode' => 'r'])
                    ->pull()
                    ->getResponses(),
                false
            );

            /** @var Response $response */
            foreach ($res as $response) {
                if ($response->getSignature() == Response::SIGNATURE_FAILURE) {
                    throw new Exception($response->getContent()['message']);
                }
            }

            $dateTimeZoneIdStructure = $res[1]->getContent()[0];

            $this->assertInstanceOf(DateTimeZoneId::class, $dateTimeZoneIdStructure);
            $this->assertEquals($datetime, (string)$dateTimeZoneIdStructure, 'unpack ' . $datetime . ' != ' . $dateTimeZoneIdStructure);

            //pack
            $res = iterator_to_array(
                $protocol
                    ->run('RETURN toString($dt)', [
                        'dt' => $dateTimeZoneIdStructure
                    ], ['mode' => 'r'])
                    ->pull()
                    ->getResponses(),
                false
            );

            // neo4j returns fraction of seconds not padded with zeros ... also contains timezone offset before timezone id
            $datetime = preg_replace("/\.?0+\[/", '[', $datetime);
            $dateTimeZoneIdStructure = preg_replace("/([+\-]\d{2}:\d{2}|Z)\[/", '[', $res[1]->getContent()[0]);
            $this->assertEquals($datetime, $dateTimeZoneIdStructure, 'pack ' . $datetime . ' != ' . $dateTimeZoneIdStructure);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Invalid value for TimeZone: Text \'' . $timezone . '\'') === 0) {
                $protocol->reset()->getResponse();
                $this->markTestSkipped('Test skipped because database is missing timezone ID ' . $timezone);
            } else {
                $this->markTestIncomplete($e->getMessage());
            }
        }
    }

    /**
     * @depends      testInit
     * @dataProvider durationProvider
     * @param string $duration
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testDuration(string $duration, AProtocol $protocol)
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN duration($d)', ['d' => $duration], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $durationStructure = $res[1]->getContent()[0];

        $this->assertInstanceOf(Duration::class, $durationStructure);
        $this->assertEquals($duration, (string)$durationStructure, 'unpack ' . $duration . ' != ' . $durationStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($d)', [
                    'd' => $durationStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertEquals($duration, $res[1]->getContent()[0], 'pack ' . $duration . ' != ' . $res[1]->getContent()[0]);
    }

    public function durationProvider(): \Generator
    {
        foreach ([
                     'P1Y',
                     'P1M',
                     'P1D',
                     'PT1H',
                     'PT1M',
                     'PT1S',
                     'P1Y2M14DT16H12M35.765S'
                 ] as $duration)
            yield $duration => [$duration];
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     * @param int $timestamp
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testLocalDateTime(int $timestamp, AProtocol $protocol)
    {
        $timestamp .= '.' . rand(0, 9e5);
        $datetime = \DateTime::createFromFormat('U.u', $timestamp)
            ->format('Y-m-d\TH:i:s.u');

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN localdatetime($dt)', [
                    'dt' => $datetime
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $localDateTimeStructure = $res[1]->getContent()[0];

        $this->assertInstanceOf(LocalDateTime::class, $localDateTimeStructure);
        $this->assertEquals($datetime, (string)$localDateTimeStructure, 'unpack ' . $datetime . ' != ' . $localDateTimeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($dt)', [
                    'dt' => $localDateTimeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $datetime = rtrim($datetime, '.0');
        $this->assertEquals($datetime, $res[1]->getContent()[0], 'pack ' . $datetime . ' != ' . $res[1]->getContent()[0]);
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     * @param int $timestamp
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testLocalTime(int $timestamp, AProtocol $protocol)
    {
        $timestamp .= '.' . rand(0, 9e5);
        $time = \DateTime::createFromFormat('U.u', $timestamp)
            ->format('H:i:s.u');

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN localtime($t)', [
                    't' => $time
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $localTimeStructure = $res[1]->getContent()[0];

        $this->assertInstanceOf(LocalTime::class, $localTimeStructure);
        $this->assertEquals($time, (string)$localTimeStructure, 'unpack ' . $time . ' != ' . $localTimeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($t)', [
                    't' => $localTimeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $time = rtrim($time, '.0');
        $this->assertEquals($time, $res[1]->getContent()[0], 'pack ' . $time . ' != ' . $res[1]->getContent()[0]);
    }

    /**
     * @depends testInit
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testNode(AProtocol $protocol)
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (a:Test) RETURN a')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Node::class, $res[1]->getContent()[0]);

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends testInit
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testPath(AProtocol $protocol)
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE p=(:Test)-[:HAS]->(:Test) RETURN p')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Path::class, $res[1]->getContent()[0]);

        foreach ($res[1]->getContent()[0]->rels() as $rel)
            $this->assertInstanceOf(UnboundRelationship::class, $rel);

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends testInit
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testPoint2D(AProtocol $protocol)
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN point({ latitude: 13.43, longitude: 56.21 })', [], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Point2D::class, $res[1]->getContent()[0]);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($p)', [
                    'p' => $res[1]->getContent()[0]
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertStringStartsWith('point(', $res[1]->getContent()[0]);
    }

    /**
     * @depends testInit
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testPoint3D(AProtocol $protocol)
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN point({ x: 0, y: 4, z: 1 })', [], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Point3D::class, $res[1]->getContent()[0]);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($p)', [
                    'p' => $res[1]->getContent()[0]
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertStringStartsWith('point(', $res[1]->getContent()[0]);
    }

    /**
     * @depends testInit
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testRelationship(AProtocol $protocol)
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (:Test)-[rel:HAS]->(:Test) RETURN rel')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Relationship::class, $res[1]->getContent()[0]);

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     * @param int $timestamp
     * @param string $timezone
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testTime(int $timestamp, string $timezone, AProtocol $protocol)
    {
        $timestamp .= '.' . rand(0, 9e5);
        $time = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
            ->format('H:i:s.uP');

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN time($t)', [
                    't' => $time
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $timeStructure = $res[1]->getContent()[0];

        $this->assertInstanceOf(Time::class, $timeStructure);
        $this->assertEquals($time, (string)$timeStructure, 'unpack ' . $time . ' != ' . $timeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($t)', [
                    't' => $timeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        // neo4j returns fraction of seconds not padded with zeros ... zero timezone offset returns as Z
        $time = preg_replace(["/\.?0+(.\d{2}:\d{2})$/", "/\+00:00$/"], ['$1', 'Z'], $time);
        $this->assertEquals($time, $res[1]->getContent()[0], 'pack ' . $time . ' != ' . $res[1]->getContent()[0]);
    }

    public function providerTimestamp(): \Generator
    {
        for ($i = 0; $i < self::$iterations; $i++) {
            $ts = $this->randomTimestamp();
            yield 'ts: ' . $ts => [$ts];
        }
    }

    public function providerTimestampTimezone(): \Generator
    {
        for ($i = 0; $i < self::$iterations; $i++) {
            $tz = \DateTimeZone::listIdentifiers()[array_rand(\DateTimeZone::listIdentifiers())];
            $ts = $this->randomTimestamp($tz);
            yield 'ts: ' . $ts . ' tz: ' . $tz => [$ts, $tz];
        }
    }

    /**
     * @param string $timezone
     * @return int
     */
    private function randomTimestamp(string $timezone = '+0000'): int
    {
        try {
            $zone = new \DateTimeZone($timezone);
            $start = new \DateTime(date('Y-m-d H:i:s', strtotime('-10 years', 0)), $zone);
            $end = new \DateTime(date('Y-m-d H:i:s', strtotime('+10 years', 0)), $zone);
            return rand($start->getTimestamp(), $end->getTimestamp());
        } catch (Exception $e) {
            return strtotime('now ' . $timezone);
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerByteArray
     * @param \Bolt\PackStream\Bytes $arr
     * @param AProtocol|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol
     */
    public function testByteArray(Bytes $arr, AProtocol $protocol)
    {
        $res = iterator_to_array(
            $protocol
                ->run('RETURN $arr', ['arr' => $arr])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($arr, $res[1]->getContent()[0]);
    }

    public function providerByteArray(): \Generator
    {
        foreach ([1, 200, 60000, 70000] as $size) {
            $arr = new Bytes();
            while (count($arr) < $size) {
                $arr[] = pack('H', mt_rand(0, 255));
            }
            yield 'bytes: ' . count($arr) => [$arr];
        }
    }

}

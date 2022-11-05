<?php

namespace Bolt\tests\structures\v1;

use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
use Exception;

trait DateTimeZoneIdTrait
{
    /**
     * @depends testInit
     * @dataProvider providerTimestampTimezone
     * @param int $timestamp
     * @param string $timezone
     * @param AProtocol $protocol
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

            $this->assertInstanceOf($this->expectedDateTimeZoneIdClass, $dateTimeZoneIdStructure);
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
}

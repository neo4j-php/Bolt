<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\Bolt;
use Bolt\connection\IConnection;
use Bolt\helpers\Auth;
use Bolt\packstream\v1\Packer;
use Bolt\protocol\V4_4;
use Bolt\protocol\V5;
use Bolt\protocol\V5_1;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Sleep;
use PhpBench\Attributes\Warmup;
use stdClass;


abstract class AbstractSocketBench
{
    protected IConnection $connection;
    private Packer $packer;

    public function __construct()
    {
        $this->packer = new Packer();
    }

    #[Revs(1000)]
    #[Iterations(5)]
    #[Warmup(5)]
    #[Sleep(1)]
    #[BeforeMethods(['configureConnection'])]
    public function benchConnect(): void
    {
        $this->connection->connect();
    }

    /**
     * @param array{message: string} $params
     * @return void
     */
    #[Revs(100)]
    #[Iterations(5)]
    #[Warmup(5)]
    #[Sleep(1)]
    #[BeforeMethods(['readyConnection'])]
    #[ParamProviders(['provideRunStatements'])]
    public function benchWrite(array $params): void
    {
        $this->connection->write($params['message']);
    }


    /**
     * @param array{length: int} $params
     * @return void
     */
    #[Revs(100)]
    #[Iterations(5)]
    #[Sleep(1)]
    #[Warmup(5)]
    #[BeforeMethods(['fillConnection'])]
    #[ParamProviders(['provideReadSizes'])]
    public function benchRead(array $params): void
    {
        $this->connection->read($params['length']);
    }

    public function provideReadSizes(): array
    {
        return [
            '4 bytes' => [ 'length' => 0x4 ],
            '15 bytes' => [ 'length' => 0xF ],
            '63 bytes' => [ 'length' => 0x4F ],
            '255 bytes' => [ 'length' => 0xFF ],
            '1023 bytes' => [ 'length' => 0x4FF ],
            '4095 bytes' => [ 'length' => 0xFFF ],
            '20479 bytes' => [ 'length' => 0x4FFF ],
            '65535 bytes' => [ 'length' => 0xFFFF ],
        ];
    }

    abstract protected function createConnection(): IConnection;

    public function readyConnection(): void
    {
        $this->configureConnection();

        $bolt = new Bolt($this->connection);
        $bolt->setProtocolVersions(5.1, 5, 4.4);
        /** @var V4_4|V5|V5_1 $protocol */
        $protocol = $bolt->build();

        $protocol->hello(Auth::basic($_ENV['NEO_USER'] ?? 'neo4j', $_ENV['NEO_PASS'] ?? 'testtest'));
    }

    public function fillConnection(): void
    {
        $this->readyConnection();

        $this->connection->write($this->packer->pack(
            0x10,
            'RETURN $x AS x',
            ['x' => bin2hex(random_bytes(0xF_FFFF))],
            new stdClass())
        );
    }

    public function provideRunStatements(): array
    {
        return array_merge(
            $this->packRun(''),
            $this->packRun(bin2hex(random_bytes(0xF))),
            $this->packRun(bin2hex(random_bytes(0xFF))),
            $this->packRun(bin2hex(random_bytes(0xFFF))),
            $this->packRun(bin2hex(random_bytes(0xFFFF))),
            $this->packRun(bin2hex(random_bytes(0xF_FFFF))),
            $this->packRun(bin2hex(random_bytes(0xFF_FFFF))),
        );
    }

    /**
     * @param string $parameter
     * @return non-empty-array<string, array{message: string}>
     * @throws \Bolt\error\PackException
     */
    private function packRun(string $parameter): array
    {
        $message = '';
        $stringPieces = $this->packer->pack(0x10, 'RETURN $x AS x', (object) ['x' => $parameter], new stdClass());
        foreach ($stringPieces as $stringPiece) {
            $message .= $stringPiece;
        }
        return [ 'Run of size: ' . mb_strlen($message) . ' bytes' => compact('message') ];
    }

    public function configureConnection(): void
    {
        $this->connection = $this->createConnection();
    }
}
<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\Bolt;
use Bolt\connection\IConnection;
use Bolt\helpers\Auth;
use Bolt\packstream\v1\Packer;
use Bolt\protocol\Response;
use Bolt\protocol\ServerState;
use Bolt\protocol\V4_4;
use Bolt\protocol\V5;
use Bolt\protocol\V5_1;
use PhpBench\Attributes\AfterMethods;
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
    public function benchIO(array $params): void
    {
        $this->connection->write($params['message']);
        $msg = '';
        while (true) {
            $header = $this->connection->read(2);
            if ($msg !== '' && ord($header[0]) == 0x00 && ord($header[1]) == 0x00)
                break;
            $length = unpack('n', $header)[1] ?? 0;
            $msg .= $this->connection->read($length);
        }

        $output = [];
        $signature = 0;
        if (!empty($msg)) {
            $output = $this->unpacker->unpack($msg);
            $signature = $this->unpacker->getSignature();

            if ($signature == Response::SIGNATURE_FAILURE) {
                $this->serverState->set(ServerState::FAILED);
            } elseif ($signature == Response::SIGNATURE_IGNORED) {
                $this->serverState->set(ServerState::INTERRUPTED);
                // Ignored doesn't have any response content
                $output = [];
            }
        }
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
     */
    private function packRun(string $parameter): array
    {
        $message = '';
        $stringPieces = $this->packer->pack(0x10, 'RETURN $x AS x', (object)['x' => $parameter], new stdClass());
        foreach ($stringPieces as $stringPiece) {
            $message .= $stringPiece;
        }
        return ['Run of size: ' . mb_strlen($message) . ' bytes' => compact('message')];
    }

    public function configureConnection(): void
    {
        $this->connection = $this->createConnection();
    }
}
<?php

namespace Bolt\protocol\ServerState;


use Bolt\protocol\AProtocol;
use Bolt\protocol\V3;
use Bolt\protocol\V4;
use Bolt\protocol\V4_3;
use function is_a;

class ServerStateFactory
{
    private int $version;
    private const IMPLEMENTATION_MAPPING = [
        0x040300 => [
            \Bolt\protocol\ServerState\V1\Defunct::class,
            \Bolt\protocol\ServerState\V1\Disconnected::class,
            \Bolt\protocol\ServerState\V3\Failed::class,
            \Bolt\protocol\ServerState\V4_3\Ready::class,
            \Bolt\protocol\ServerState\V4_0\Streaming::class,
            \Bolt\protocol\ServerState\V3\TxReady::class,
            \Bolt\protocol\ServerState\V4_0\TxStreaming::class,
        ],
        0x040000 => [
            \Bolt\protocol\ServerState\V1\Defunct::class,
            \Bolt\protocol\ServerState\V1\Disconnected::class,
            \Bolt\protocol\ServerState\V3\Failed::class,
            \Bolt\protocol\ServerState\V3\Ready::class,
            \Bolt\protocol\ServerState\V4_0\Streaming::class,
            \Bolt\protocol\ServerState\V3\TxReady::class,
            \Bolt\protocol\ServerState\V4_0\TxStreaming::class,
        ],
        0x030000 => [
            \Bolt\protocol\ServerState\V1\Defunct::class,
            \Bolt\protocol\ServerState\V1\Disconnected::class,
            \Bolt\protocol\ServerState\V3\Failed::class,
            \Bolt\protocol\ServerState\V3\Ready::class,
            \Bolt\protocol\ServerState\V3\TxReady::class,
            \Bolt\protocol\ServerState\V3\TxStreaming::class,
        ],
        0x010000 => [
            \Bolt\protocol\ServerState\V1\Connected::class,
            \Bolt\protocol\ServerState\V1\Defunct::class,
            \Bolt\protocol\ServerState\V1\Disconnected::class,
            \Bolt\protocol\ServerState\V1\Failed::class,
            \Bolt\protocol\ServerState\V1\Interrupted::class,
            \Bolt\protocol\ServerState\V1\Ready::class,
            \Bolt\protocol\ServerState\V1\Streaming::class
        ],
    ];

    public function __construct(int $version)
    {
        $this->version = $version;
    }

    public function buildNewState(int $state): IServerState
    {
        return new (self::IMPLEMENTATION_MAPPING[$this->version][$state] ?? Unknown::class)();
    }

    public static function createFromProtocol(AProtocol $protocol): self
    {
        if (is_a($protocol, V4_3::class)) {
            return new self(0x040300);
        }
        if (is_a($protocol, V4::class)) {
            return new self(0x040300);
        }
        if (is_a($protocol, V3::class)) {
            return new self(0x030000);
        }

        return new self(0x010000);
    }
}
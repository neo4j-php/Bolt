<?php

namespace Bolt\protocol;

use Bolt\PackStream\{IPacker, IUnpacker};
use Bolt\Socket;

/**
 * Abstract class AProtocol
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
abstract class AProtocol implements IProtocol
{

    protected const SUCCESS = 0x70;
    protected const FAILURE = 0x7F;
    protected const IGNORED = 0x7E;
    protected const RECORD = 0x71;

    /**
     * @var IPacker
     */
    protected $packer;

    /**
     * @var IUnpacker
     */
    protected $unpacker;

    /**
     * @var Socket
     */
    protected $socket;

    /**
     * AProtocol constructor.
     * @param IPacker $packer
     * @param IUnpacker $unpacker
     * @param Socket $socket
     */
    public function __construct(IPacker $packer, IUnpacker $unpacker, Socket $socket)
    {
        $this->packer = $packer;
        $this->unpacker = $unpacker;
        $this->socket = $socket;
    }

    public function begin(...$args): bool
    {
        return false;
    }

    public function commit(...$args): bool
    {
        return false;
    }

    public function rollback(...$args): bool
    {
        return false;
    }

    public function goodbye(...$args)
    {

    }

}

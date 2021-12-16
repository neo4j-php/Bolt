<?php

namespace Bolt\protocol;

use Bolt\error\MessageException;
use Bolt\error\PackException;
use Exception;

/**
 * Class Protocol version 3
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-3.html
 * @package Bolt\protocol
 */
class V3 extends V2
{

    /**
     * @inheritDoc
     * @deprecated Replaced with HELLO message
     */
    public function init(...$args): array
    {
        $args[] = null;
        return $this->hello(...$args);
    }

    /**
     * Send HELLO message
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function hello(...$args): array
    {
        if (count($args) < 1) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x01, $args[0]));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $output;
    }

    /**
     * @param string|array ...$args query, parameters, extra
     * @return array
     * @throws Exception
     */
    public function run(...$args): array
    {
        if (empty($args)) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(
            0x10,
            $args[0],
            (object)($args[1] ?? []),
            (object)($args[2] ?? [])
        ));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->reset();
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS ? $output : [];
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function reset(): bool
    {
        $this->write($this->packer->pack(0x0F));
        return true;
    }

    /**
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function begin(...$args): bool
    {
        $this->write($this->packer->pack(0x11, (object)($args[0] ?? [])));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->reset();
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function commit(): bool
    {
        $this->write($this->packer->pack(0x12));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->reset();
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function rollback(): bool
    {
        $this->write($this->packer->pack(0x13));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->reset();
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    public function goodbye()
    {
        $this->write($this->packer->pack(0x02));
    }

}

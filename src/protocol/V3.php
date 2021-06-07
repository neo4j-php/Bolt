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
 * @package Bolt\protocol
 */
class V3 extends V2
{

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function init(...$args): array
    {
        return $this->hello(...$args);
    }

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function hello(...$args): array
    {
        if (count($args) < 4) {
            throw new PackException('Wrong arguments count');
        }

        $this->write($this->packer->pack(0x01, [
            'user_agent' => $args[0],
            'scheme' => $args[1],
            'principal' => $args[2],
            'credentials' => $args[3]
        ]));
        $output = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($output['message'] . ' (' . $output['code'] . ')');
        }

        return $signature == self::SUCCESS ? $output : [];
    }

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function run(...$args)
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
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function reset(...$args): bool
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
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function commit(...$args): bool
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
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function rollback(...$args): bool
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
     * @param mixed ...$args
     * @throws Exception
     */
    public function goodbye(...$args)
    {
        $this->write($this->packer->pack(0x02));
    }

}

<?php

namespace Bolt\protocol;

/**
 * Interface IProtocol
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
interface IProtocol
{

    /**
     * Send INIT/HELLO message
     * @param mixed ...$args
     * @return array
     */
    public function init(...$args): array;

    /**
     * Send RUN message
     * @param mixed ...$args
     * @return mixed
     */
    public function run(...$args);

    /**
     * Send PULL/PULL_ALL message
     * @param mixed ...$args
     * @return mixed
     */
    public function pullAll(...$args);

    /**
     * Send DISCARD/DISCARD_ALL message
     * @param mixed ...$args
     * @return bool
     */
    public function discardAll(...$args): bool;

    /**
     * Send RESET message
     * @param mixed ...$args
     */
    public function reset(...$args): bool;

    /**
     * Send BEGIN message
     * @param mixed ...$args
     * @return bool
     */
    public function begin(...$args): bool;

    /**
     * Send COMMIT message
     * @param mixed ...$args
     * @return bool
     */
    public function commit(...$args): bool;

    /**
     * Send ROLLBACK message
     * @param mixed ...$args
     * @return bool
     */
    public function rollback(...$args): bool;

    /**
     * Send GOODBYE message
     * @param mixed ...$args
     */
    public function goodbye(...$args);

}

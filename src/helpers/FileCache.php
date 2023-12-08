<?php

namespace Bolt\helpers;

use Psr\SimpleCache\CacheInterface;

/**
 * Class FileCache
 *
 * @package Bolt\helpers
 */
class FileCache implements CacheInterface
{

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key))
            return file_get_contents(getcwd() . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $key);
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if (!file_exists(getcwd() . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR))
            mkdir(getcwd() . DIRECTORY_SEPARATOR . 'temp');
        return (bool)file_put_contents(getcwd() . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $key, $value);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        if ($this->has($key))
            return unlink(getcwd() . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $key);
        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        foreach (scandir(getcwd() . DIRECTORY_SEPARATOR . 'temp') as $file) {
            if ($file == '.' || $file == '..')
                continue;
            unlink(getcwd() . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $file);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        // TODO: Implement getMultiple() method.
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        // TODO: Implement setMultiple() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        // TODO: Implement deleteMultiple() method.
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return file_exists(getcwd() . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $key);
    }
}

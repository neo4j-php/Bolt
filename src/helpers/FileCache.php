<?php

namespace Bolt\helpers;

use Psr\SimpleCache\CacheInterface;

/**
 * Class FileCache
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\helpers
 */
class FileCache implements CacheInterface
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = $_ENV['TEMP_DIR'] . DIRECTORY_SEPARATOR . 'php-bolt-filecache' . DIRECTORY_SEPARATOR;
        if (!file_exists($this->tempDir)) {
            mkdir($_ENV['TEMP_DIR'] . DIRECTORY_SEPARATOR . 'php-bolt-filecache');
        }

        // clean old
        foreach (scandir($this->tempDir) as $file) {
            if ($file == '.' || $file == '..')
                continue;
            if (filemtime($this->tempDir . $file) < strtotime('-1 hour'))
                unlink($this->tempDir . $file);
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? file_get_contents($this->tempDir . $key) : $default;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        return is_int(file_put_contents($this->tempDir . $key, $value));
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete(string $key): bool
    {
        return $this->has($key) && unlink($this->tempDir . $key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        foreach (scandir(rtrim($this->tempDir, DIRECTORY_SEPARATOR)) as $file) {
            if ($file == '.' || $file == '..')
                continue;
            unlink($this->tempDir . $file);
        }
        return true;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<string> $keys A list of keys that can be obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, mixed> A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $this->get($key, $default);
        }
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                        the driver supports TTL then the library may set a default value
     *                                        for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     *  NOTE: It is recommended that has() is only to be used for cache warming type purposes
     *  and not to be used within your live applications operations for get/set, as this method
     *  is subject to a race condition where your has() will return true and immediately after,
     *  another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return file_exists($this->tempDir . $key);
    }
}

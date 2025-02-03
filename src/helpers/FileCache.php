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
    /**
     * @var resource[]
     */
    private array $handles = [];

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-filecache' . DIRECTORY_SEPARATOR;
        
        if (!file_exists($this->tempDir)) {
            mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-filecache', recursive: true);
        }
        // dotted directory to hold "time-to-live" informations
        if (!file_exists($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR)) {
            mkdir($this->tempDir . '.ttl');
        }

        register_shutdown_function([$this, 'shutdown']);
    }

    private function shutdown(): void
    {
        foreach ($this->handles as $handle) {
            flock($handle, LOCK_UN);
            fclose($handle);
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
        if (array_key_exists($key, $this->handles)) {
            rewind($this->handles[$key]);
            return unserialize(stream_get_contents($this->handles[$key]), ['allowed_classes' => false]);
        }

        if ($this->has($key)) {
            $data = file_get_contents($this->tempDir . $key);
            if ($data !== false) {
                return unserialize($data, ['allowed_classes' => false]);
            }
        }

        return $default;
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
        if ($ttl) {
            is_writable($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR) && file_put_contents(
                $this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key,
                $ttl instanceof \DateInterval ? (new \DateTime())->add($ttl)->getTimestamp() : $ttl
            );
        }

        if (array_key_exists($key, $this->handles)) {
            ftruncate($this->handles[$key], 0);
            rewind($this->handles[$key]);
            return fwrite($this->handles[$key], serialize($value)) !== false;
        }

        return is_writable($this->tempDir) && file_put_contents($this->tempDir . $key, serialize($value)) !== false;
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
        if ($this->has($key)) {
            if (file_exists($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key)) {
                @unlink($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key);
            }
            return @unlink($this->tempDir . $key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        array_map('unlink', glob($this->tempDir . '*.*'));
        array_map('unlink', glob($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . '*.*'));
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
        // remove file when is expired
        if (
            file_exists($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key)
            && intval(file_get_contents($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key)) < time()
        ) {
            @unlink($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key);
            @unlink($this->tempDir . $key);
        }

        return file_exists($this->tempDir . $key) && is_file($this->tempDir . $key);
    }

    /**
     * Lock a key to prevent other processes from modifying it
     * @param string $key
     * @return bool
     */
    public function lock(string $key): bool
    {
        $this->handles[$key] = fopen($this->tempDir . $key, 'c+');
        return flock($this->handles[$key], LOCK_EX);
    }

    /**
     * Unlock a key
     * @param string $key
     * @return void
     */
    public function unlock(string $key): void
    {
        if (array_key_exists($key, $this->handles)) {
            flock($this->handles[$key], LOCK_UN);
            fclose($this->handles[$key]);
            unset($this->handles[$key]);
        }
    }
}

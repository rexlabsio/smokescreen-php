<?php

namespace Rexlabs\Smokescreen\Includes;

use Rexlabs\Smokescreen\Exception\ParseIncludesException;

/**
 * Container object for managing include keys and optional mapped parameters.
 */
class Includes
{
    /** @var array */
    protected $keys;

    /** @var array */
    protected $params = [];

    /**
     * Provide a list of keys, and optional parameters for those keys.
     *
     * @param array $keys
     * @param array $params
     */
    public function __construct(array $keys = [], array $params = [])
    {
        $this->set($keys);
        $this->params = $params;
    }

    /**
     * @param array $keys
     *
     * @return $this
     */
    public function set(array $keys)
    {
        $this->keys = $this->expandKeys($keys);

        return $this;
    }

    /**
     * Provide a list of keys, so that depth-keys are expanded to include parents
     * Given a two keys of [user.id, photo.url], this method will return [user, photo, user.id, photo.url].
     *
     * @param array $keys
     *
     * @return array
     */
    protected function expandKeys(array $keys): array
    {
        $allKeys = [];

        // Search all keys that contain the '.', and add their parents all the way up.
        foreach ($keys as $key) {
            $allKeys[] = $key;
            while (($dot = \strrpos($key, '.')) !== false) {
                $key = \substr($key, 0, $dot);
                $allKeys[] = $key;
            }
        }

        // We probably created some dupes ...
        $allKeys = \array_unique($allKeys);

        return $allKeys;
    }

    /**
     * Add one or more keys.
     * Duplicate keys will be silently ignored.
     *
     * @param string|array|mixed $keys
     *
     * @return $this
     */
    public function add($keys)
    {
        foreach ($this->expandKeys((array) $keys) as $key) {
            if (!$this->has($key)) {
                $this->keys[] = $key;
            }
        }

        return $this;
    }

    /**
     * Determine if a given key exists.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return \in_array($key, $this->keys(), true);
    }

    /**
     * Get an array of all keys, note this will include expanded keys (parents).
     *
     * @return array
     */
    public function keys(): array
    {
        return $this->keys;
    }

    /**
     * Returns true if any keys have been set.
     *
     * @return bool
     */
    public function hasKeys(): bool
    {
        return \count($this->keys) > 0;
    }

    /**
     * Remove one or more keys.
     *
     * @param array|string $keys
     */
    public function remove($keys)
    {
        $keys = (array) $keys; // Cast all the things
        $this->keys = array_filter($this->keys(), function ($key) use ($keys) {
            foreach ($keys as $remove) {
                if (preg_match('/^'.preg_quote($remove, '/').'(\..+)?$/', $key)) {
                    // Keys and descendant keys will be removed
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Only keys that don't descend (eg. don't contain a dot).
     *
     * @return array
     */
    public function baseKeys(): array
    {
        return \array_values(\array_filter($this->keys(), function ($key) {
            return \strpos($key, '.') === false;
        }));
    }

    /**
     * Return the params associative array.
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Returns true if any parameters have been set.
     *
     * @return bool
     */
    public function hasParams(): bool
    {
        return \count($this->params) > 0;
    }

    /**
     * An array of parameters indexed by key.
     *
     * @param array $params
     *
     * @throws ParseIncludesException
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        // Is this an associative array?
        if (!empty($params)) {
            if (\count(array_filter(array_keys($params), '\is_string')) < 1) {
                throw new ParseIncludesException('Parameters must be an associative array indexed by key');
            }
        }
        $this->params = $params;

        return $this;
    }

    /**
     * Get the parameters for the given key
     * An empty array will be returned for non-matched keys.
     *
     * @param string $key
     *
     * @return array
     */
    public function paramsFor(string $key): array
    {
        $params = [];
        if (isset($this->params[$key])) {
            $params = $this->params[$key];
        }

        return $params;
    }

    /**
     * All keys and params will be reset.
     *
     * @return $this
     */
    public function reset()
    {
        $this->keys = [];
        $this->params = [];

        return $this;
    }

    /**
     * Returns a new Includes object containing all keys spliced below the given parent key,
     * as well as any mapped params.
     *
     * @param string $parentKey
     *
     * @return static
     *
     * @see Includes::descendantsOf()
     * @see Includes::allParamsFor()
     */
    public function splice($parentKey)
    {
        // Get all the descendant keys
        $keys = $this->descendantsOf($parentKey);

        // Cool, we have a list of keys, now let's get the associated params
        $params = $this->allParamsFor($keys); // That was hard

        return new static(
            $keys,
            $params
        );
    }

    /**
     * Given a parent key, return all the descendant keys (without the parent prefix)
     * Example: Given key is "user", the current keys are ["user.id", "user.photos", "user.photos.id"]
     * The result will be: ["id", "photos", "photos.id"].
     *
     * @param string $parentKey
     *
     * @return array
     */
    public function descendantsOf(string $parentKey): array
    {
        // First get child keys of the given parent key
        $keys = [];
        foreach ($this->keys() as $key) {
            if (strpos($key, "{$parentKey}.") === 0) {
                // Found a match, chop off the parent key
                $keys[] = preg_replace(
                    '/^'.preg_quote($parentKey.'.', '/').'/', // Starts with parent
                    '', // Remove it
                    $key
                );
            }
        }

        return $keys;
    }

    /**
     * Get the parameters for the given keys as an associative array indexed by key
     * An empty array will be returned if there are no matched keys.
     *
     * @param array $keys
     *
     * @return array
     */
    public function allParamsFor(array $keys): array
    {
        $params = [];
        foreach ($keys as $key) {
            if (isset($this->params[$key])) {
                $params[$key] = $this->params[$key];
            }
        }

        return $params;
    }
}

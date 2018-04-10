<?php

namespace Rexlabs\Smokescreen\Definition;

use Rexlabs\Smokescreen\Exception\ParseDefinitionException;
use Rexlabs\Smokescreen\Helpers\StrHelper;

class DefinitionParser
{
    /**
     * A list of keys that are allowed to be present
     *
     * @var array
     */
    protected $allowedKeys = [];

    /**
     * Short keys map a shortcut key to a specific directive.
     *
     * @var array
     */
    protected $shortKeys = [];

    /**
     * Parses a definition string into an array.
     * Supports a value like integer|arg1:val|arg2:val|arg3
     *
     * @param string $str
     *
     * @return array
     * @throws \Rexlabs\Smokescreen\Exception\ParseDefinitionException
     */
    public function parse($str): array
    {
        $definition = [];

        if (empty($str)) {
            // Return empty definition
            return $definition;
        }

        // Directives are delimited with a pipe operator
        foreach (preg_split('/\s*\|\s*/', $str) as $directive) {
            // May consist of "directive:value" or it may just be "directive".
            $parts = explode(':', $directive, 2);
            $key = $parts[0];
            $value = $parts[1] ?? null;

            if (isset($this->shortKeys[$key])) {
                if ($value !== null) {
                    // If a value was also provided, we'll store that in a separate entry.
                    $definition[$this->normalizeKey($key)] = $value;
                }
                $value = $key;
                $key = $this->shortKeys[$key];
            }

            if (!$this->isAllowedKey($key)) {
                throw new ParseDefinitionException("Unsupported key '$key'");
            }

            // Normalise our directive (as snake_case) and store the value.
            $definition[$this->normalizeKey($key)] = $value;
        }



        return $definition;
    }

    /**
     * Normalize a directive key
     * @param string $key
     *
     * @return string
     */
    protected function normalizeKey($key): string
    {
        return StrHelper::snakeCase(strtolower($key));
    }

    /**
     * Set a list of keys which are allowed.
     * An empty array will allow any key.
     * An exception will be thrown while parsing when the allowed keys are not empty, and the
     * directive key is not present in this list.
     * @param array $keys
     *
     * @return $this
     */
    public function setAllowedKeys(array $keys)
    {
        $this->allowedKeys = $keys;

        return $this;
    }

    /**
     * Determine if a given key is permitted.
     * @param $key
     *
     * @return bool
     */
    protected function isAllowedKey($key): bool
    {
        return empty($this->allowedKeys) || \in_array($key, $this->allowedKeys, true);
    }

    /**
     * Register one or more shortcut keys which map to a directive.
     * For example a shortcut key of 'integer' can mapped to a directive of 'type'.
     * This would allow a user to specify a directive of simply 'integer', instead
     * of 'type:integer'.
     *
     * @param string $directive
     * @param array  $keys
     *
     * @return $this
     */
    public function addShortKeys(string $directive, array $keys)
    {
        foreach ($keys as $key) {
            $this->shortKeys[$key] = $directive;
        }

        return $this;
    }
}
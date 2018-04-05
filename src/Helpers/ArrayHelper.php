<?php

namespace Rexlabs\Smokescreen\Helpers;

class ArrayHelper
{
    /**
     * Mutate an array using dot-notation.
     *
     * @param array  $array
     * @param string $writeKey
     * @param mixed  $value
     *
     * @return void
     */
    public static function mutate(array &$array, string $writeKey, $value)
    {
        $ref = &$array;
        $keys = explode('.', $writeKey);
        while (($key = array_shift($keys)) !== null) {
            $hasMoreKeys = \count($keys) > 0;
            if ($hasMoreKeys) {
                if (!\array_key_exists($key, $ref)) {
                    // Prepare the array
                    $ref[$key] = [];
                }
                $ref = &$ref[$key];
                continue;
            }

            // Last key
            $ref[$key] = $value;
        }
    }
}
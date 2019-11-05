<?php

namespace Rexlabs\Smokescreen\Helpers;

use function array_key_exists;
use function is_array;

class ArrayHelper
{
    /**
     * Get an array value using dot-notation.
     *
     * @param array  $array
     * @param string $readKey
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public static function get(array $array, string $readKey, $defaultValue = null)
    {
        $value = $array;
        foreach (explode('.', $readKey) as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                $value = null;
                break;
            }
            $value = $value[$key];
        }

        //Get a default value
        if ($value === null && $defaultValue !== null) {
            return $defaultValue;
        }

        return $value;
    }
    
    /**
     * Mutate an array using dot-notation.
     *
     * @param array  $array
     * @param string $writeKey
     * @param mixed  $value
     * @param bool   $append Append value to array at write key
     *
     * @return void
     */
    public static function mutate(
        array &$array, 
        string $writeKey, 
        $value, bool 
        $append = false
    ) {
        $ref = &$array;
        $keys = explode('.', $writeKey);
        while (($key = array_shift($keys)) !== null) {
            $hasMoreKeys = count($keys) > 0;
            if ($hasMoreKeys) {
                if (!array_key_exists($key, $ref)) {
                    // Prepare the array
                    $ref[$key] = [];
                }
                $ref = &$ref[$key];
                continue;
            }

            // Last key
            if ($append) {
                $ref[$key][] = $value;
            } else {
                $ref[$key] = $value;
            }
        }
    }
}

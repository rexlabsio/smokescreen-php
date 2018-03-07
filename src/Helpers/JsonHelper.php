<?php

namespace Rexlabs\Smokescreen\Helpers;

use Rexlabs\Smokescreen\Exception\JsonEncodeException;

class JsonHelper
{
    /**
     * Encodes data as a JSON string representation.
     * @param mixed $data
     * @param int   $options
     *
     * @return string
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     */
    public static function encode($data, $options = 0): string
    {
        $json = json_encode($data, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonEncodeException(json_last_error_msg());
        }

        return $json;
    }
}
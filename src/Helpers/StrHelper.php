<?php

namespace Rexlabs\Smokescreen\Helpers;

class StrHelper
{
    public static function studlyCase($str)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }

    public static function snakeCase($str)
    {
        if (! ctype_lower($str)) {
            $str = preg_replace('/\s+/u', '', ucwords($str));
            $str = preg_replace('/(.)(?=[A-Z])/u', '$1_', $str);
            $str = preg_replace('/\W+/', '_', $str);
            $str = mb_strtolower($str, 'UTF-8');
        }

        return $str;
    }
}

<?php
namespace Rexlabs\Smokescreen\Helpers;

class StrHelper
{
    public static function studlyCase($str)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }
}
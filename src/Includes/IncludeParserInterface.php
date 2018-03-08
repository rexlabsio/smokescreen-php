<?php

namespace Rexlabs\Smokescreen\Includes;

interface IncludeParserInterface
{
    public function parse(string $str): Includes;
}

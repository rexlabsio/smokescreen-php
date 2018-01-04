<?php
namespace RexSoftware\Smokescreen\Includes;

interface IncludeParserInterface
{
    public function parse(string $str): Includes;
}
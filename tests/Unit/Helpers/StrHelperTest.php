<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Helpers\StrHelper;

class StrHelperTest extends TestCase
{
    /** @test */
    public function convert_to_studly_case()
    {
        $this->assertEquals('Test', StrHelper::studlyCase('test'));
        $this->assertEquals('SnakeCase', StrHelper::studlyCase('snake_case'));
        $this->assertEquals('SnakeCase', StrHelper::studlyCase('_snake_case'));
        $this->assertEquals('KebabCase', StrHelper::studlyCase('kebab-case'));
    }
}
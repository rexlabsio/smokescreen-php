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

    /** @test */
    public function convert_to_snake_case()
    {
        $this->assertEquals('snake_case', StrHelper::snakeCase('snake_case'));
        $this->assertEquals('studly_case', StrHelper::snakeCase('StudlyCase'));
        $this->assertEquals('kebab_case', StrHelper::snakeCase('kebab-case'));
        $this->assertEquals('dot_property', StrHelper::snakeCase('dot.property'));
    }
}

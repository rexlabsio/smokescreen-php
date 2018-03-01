<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Transformer;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Exception\ParseDefinitionException;
use Rexlabs\Smokescreen\Transformer\AbstractTransformer;

class AbstractTransformerTest extends TestCase
{
    /** @test */
    public function can_declare_includes_with_plain_array()
    {
        $transformer = new class extends AbstractTransformer
        {
            protected $includes = [
                'user',
                'account',
            ];
        };

        $this->assertEquals([
            'user',
            'account',
        ], $transformer->getAvailableIncludes());
    }

    /** @test */
    public function can_declare_includes_with_assoc_array()
    {
        $transformer = new class extends AbstractTransformer
        {
            protected $includes = [
                'user'    => 'method:includeUser',
                'account' => 'method:includeAccount',
            ];
        };

        $this->assertEquals([
            'user',
            'account',
        ], $transformer->getAvailableIncludes());
    }

    /** @test */
    public function can_get_default_includes()
    {
        $transformer = new class extends AbstractTransformer
        {
            protected $includes = [
                'user',
                'account',
                'phone' => 'default',
                'address' => 'default',
            ];
        };

        $this->assertEquals([
            'user',
            'account',
            'phone',
            'address',
        ], $transformer->getAvailableIncludes());

        $this->assertEquals([
            'phone',
            'address',
        ], $transformer->getDefaultIncludes());
    }

    /** @test */
    public function bad_include_definition_throws_exception()
    {
        $transformer = new class extends AbstractTransformer
        {
            protected $includes = [
                'user',
                'account',
                'phone' => '::bad',
            ];
        };

        $this->expectException(ParseDefinitionException::class);
        $transformer->getIncludeMap();
    }
}
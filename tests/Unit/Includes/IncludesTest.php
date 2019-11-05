<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Includes;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Exception\ParseIncludesException;
use Rexlabs\Smokescreen\Includes\IncludeParser;

class IncludesTest extends TestCase
{
    /** @test */
    public function base_keys_only_returns_top_level_keys()
    {
        $includes = (new IncludeParser())->parse('category,comments{user},user{id}');

        $this->assertArraySubset([
            'category',
            'comments',
            'user',
        ], $includes->baseKeys());
    }

    /** @test */
    public function splice_returns_child_includes_without_parent()
    {
        $includes = (new IncludeParser())->parse('cast{actor,movie}');
        $castIncludes = $includes->splice('cast');

        $this->assertArraySubset([
            'cast',
        ], $includes->baseKeys());

        $this->assertArraySubset([
            'actor',
            'movie',
        ], $castIncludes->baseKeys());
    }

    /** @test */
    public function can_add_keys()
    {
        $includes = (new IncludeParser())->parse('cast{actor,movie}');
        $this->assertCount(3, $includes->keys());
        $this->assertTrue($includes->has('cast'));
        $this->assertTrue($includes->has('cast.actor'));
        $this->assertTrue($includes->has('cast.movie'));

        $includes->add('cast.actor'); // dupe
        $this->assertCount(3, $includes->keys());

        $includes->add('title');
        $this->assertTrue($includes->has('title'));
        $this->assertCount(4, $includes->keys());

        $includes->add('cast.actor.name');
        $this->assertTrue($includes->has('cast.actor.name'));
        $this->assertCount(5, $includes->keys());

        $includes->add('cast.siblings.name');
        $this->assertTrue($includes->has('cast.siblings'));
        $this->assertTrue($includes->has('cast.siblings.name'));
        $this->assertCount(7, $includes->keys());
    }

    /** @test */
    public function can_remove_keys()
    {
        $includes = (new IncludeParser())->parse('title,cast{actor,movie}');
        $this->assertCount(4, $includes->keys());

        $includes->remove('title');
        $this->assertCount(3, $includes->keys());

        $includes->remove('cast');
        $this->assertCount(0, $includes->keys());
    }

    /** @test */
    public function can_reset_includes()
    {
        $includes = (new IncludeParser())->parse('title,cast{actor,movie}:limit(5)');
        $this->assertTrue($includes->hasKeys());
        $this->assertTrue($includes->hasParams());

        $includes->reset();
        $this->assertFalse($includes->hasKeys());
        $this->assertFalse($includes->hasParams());
    }

    /**
     * @test
     *
     * @throws ParseIncludesException
     */
    public function can_set_params()
    {
        $includes = (new IncludeParser())->parse('movies:limit(10)');
        $this->assertEquals(['movies' => ['limit' => 10]], $includes->params());

        $this->expectException(ParseIncludesException::class);
        $includes->setParams([1, 2]);
    }
}

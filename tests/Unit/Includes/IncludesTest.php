<?php
namespace RexSoftware\Smokescreen\Tests\Unit\Includes;

use PHPUnit\Framework\TestCase;
use RexSoftware\Smokescreen\Includes\IncludeParser;

class IncludesTest extends TestCase
{
    /** @test */
    public function base_keys_only_returns_top_level_keys()
    {
        $includes = (new IncludeParser)->parse('category,comments{user},user{id}');

        $this->assertArraySubset([
            'category',
            'comments',
            'user',
        ], $includes->baseKeys());
    }

    /** @test */
    public function splice_returns_child_includes_without_parent() {
        $includes = (new IncludeParser)->parse('cast{actor,movie}');
        $castIncludes = $includes->splice('cast');

        $this->assertArraySubset([
            'cast'
        ], $includes->baseKeys());

        $this->assertArraySubset([
            'actor', 'movie'
        ], $castIncludes->baseKeys());
    }
}
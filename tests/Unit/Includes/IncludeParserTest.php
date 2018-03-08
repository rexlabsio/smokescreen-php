<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Includes;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Includes\IncludeParser;

class IncludeParserTest extends TestCase
{
    /** @test */
    public function parse_empty_string_gives_empty_object()
    {
        $includes = (new IncludeParser())->parse('');
        $this->assertEmpty($includes->keys());
    }

    /** @test */
    public function can_parse_child_includes()
    {
        $includes = (new IncludeParser())->parse('cast{actor,movie}');
        $this->assertTrue($includes->has('cast'));
        $this->assertTrue($includes->has('cast.actor'));
        $this->assertTrue($includes->has('cast.movie'));
    }

    /** @test */
    public function can_parse_includes_with_params()
    {
        $includes = (new IncludeParser())->parse('cast{actor,movies:limit(3)}:offset(5):anything(test)');
        $this->assertTrue($includes->has('cast'));
        $this->assertTrue($includes->has('cast.actor'));
        $this->assertTrue($includes->has('cast.movies'));
        $this->assertEquals(['offset' => 5, 'anything' => 'test'], $includes->paramsFor('cast'));
        $this->assertEquals(['limit' => 3], $includes->paramsFor('cast.movies'));
    }

    /** @test */
    public function whitespace_is_ignored()
    {
        // Spaced includes
        $includes = (new IncludeParser())->parse('title,summary, id,user{id, email} ');
        $this->assertTrue($includes->has('user'));
        $this->assertTrue($includes->has('user.id'));
        $this->assertTrue($includes->has('user.email'));
    }
}

<?php
namespace RexSoftware\Smokescreen\Tests\Unit\Includes;

use PHPUnit\Framework\TestCase;
use RexSoftware\Smokescreen\Includes\IncludeParser;

class IncludeParserTest extends TestCase
{
    /** @test */
    public function can_parse_child_includes() {
        $includes = (new IncludeParser)->parse('cast{actor,movie}');
        $this->assertTrue($includes->has('cast'));
        $this->assertTrue($includes->has('cast.actor'));
        $this->assertTrue($includes->has('cast.movie'));
    }

    /** @test */
    public function whitespace_is_ignored()
    {
        // Spaced includes
        $includes = (new IncludeParser)->parse('title,summary, id,user{id, email} ');
        $this->assertTrue($includes->has('user'));
        $this->assertTrue($includes->has('user.id'));
        $this->assertTrue($includes->has('user.email'));
        
    }
}
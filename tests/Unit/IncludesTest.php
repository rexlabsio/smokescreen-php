<?php
/**
 * smokescreen
 *
 * User: rhys
 * Date: 6/1/18
 * Time: 5:55 PM
 */

namespace RexSoftware\Smokescreen\Tests;

use PHPUnit\Framework\TestCase;
use RexSoftware\Smokescreen\Includes\IncludeParser;

class IncludesTest extends TestCase
{
    /**
     * Splice doesn't seem
     */
    public function test_splice() {
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
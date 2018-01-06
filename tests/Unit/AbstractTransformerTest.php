<?php
/**
 * smokescreen
 *
 * User: rhys
 * Date: 6/1/18
 * Time: 10:42 PM
 */

namespace RexSoftware\Smokescreen\Tests;

use PHPUnit\Framework\TestCase;
use RexSoftware\Smokescreen\Transformer\AbstractTransformer;

class AbstractTransformerTest extends TestCase
{
    /**
     * Test that includes defined as array values are parsed correctly
     */
    public function test_get_includes_array() {
        $transformer = new class extends AbstractTransformer {
            protected $includes = [
                'user',
                'account'
            ];
        };

        $this->assertArraySubset([
            'user', 'account'
        ], $transformer->getAvailableIncludes());
    }

    /**
     * Test that includes defined as assoc are parsed
     */
    public function test_get_includes_assoc() {
        $transformer = new class extends AbstractTransformer {
            protected $includes = [
                'user' => 'default',
                'account' => 'default',
            ];
        };

        $this->assertArraySubset([
            'user', 'account'
        ], $transformer->getAvailableIncludes());
    }

}
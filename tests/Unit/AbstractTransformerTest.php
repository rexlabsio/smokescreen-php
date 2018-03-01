<?php
namespace Rexlabs\Smokescreen\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Transformer\AbstractTransformer;

class AbstractTransformerTest extends TestCase
{
    /** @test */
    public function can_declare_includes_with_plain_array() {
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

    /** @test */
    public function can_declare_includes_with_assoc_array() {
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
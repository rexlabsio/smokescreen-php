<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Transformer;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Includes\Includes;
use Rexlabs\Smokescreen\Transformer\Pipeline;
use Rexlabs\Smokescreen\Transformer\Scope;

/**
 * Class PipelineTest
 *
 * @package Rexlabs\Smokescreen\Tests\Unit\Transformer
 */
class PipelineTest extends TestCase
{
    /**
     * @test
     * @return void
     * @throws IncludeException
     */
    public function null_root_resource_returns_null()
    {
        $pipeline = new Pipeline();
        $result = $pipeline->run(new Scope(null, new Includes()));
        $this->assertNull($result);
    }
}

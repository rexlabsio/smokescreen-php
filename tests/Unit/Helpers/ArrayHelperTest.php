<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Helpers\ArrayHelper;

class ArrayHelperTest extends TestCase
{
    /** @test */
    public function mutate_array()
    {
        $data = [];

        ArrayHelper::mutate($data, 'test', 'value');
        $this->assertEquals([
            'test' => 'value'
        ], $data);

        ArrayHelper::mutate($data, 'deep.test', 'value');
        $this->assertEquals([
            'test' => 'value',
            'deep' => [
                'test' => 'value',
            ]
        ], $data);

        ArrayHelper::mutate($data, 'even.deeper.test', 'value');
        $this->assertEquals([
            'test' => 'value',
            'deep' => [
                'test' => 'value',
            ],
            'even' => [
                'deeper' => [
                    'test' => 'value',
                ]
            ]
        ], $data);

        ArrayHelper::mutate($data, 'even.deeper.test', 'new_value');
        $this->assertEquals([
            'test' => 'value',
            'deep' => [
                'test' => 'value',
            ],
            'even' => [
                'deeper' => [
                    'test' => 'new_value',
                ]
            ]
        ], $data);
    }
}

<?php
/**
 * smokescreen
 *
 * User: rhys
 * Date: 7/1/18
 * Time: 4:15 PM
 */

namespace RexSoftware\Smokescreen\Tests;

use PHPUnit\Framework\TestCase;
use RexSoftware\Smokescreen\Transformer\AbstractTransformer;
use RexSoftware\Smokescreen\Smokescreen;

class SmokescreenTest extends TestCase
{
    /** @test */
    public function custom_include_method_used() {
        /* Test that a custom include method is called on the transformer */

        $transformer = new class extends AbstractTransformer {

            public $tokenTransformer;
            protected $includes = [
                'user_api_token' => 'method:includeTheUserApiToken'
            ];

            public function transform($item) {
                return [
                    'username' => $item['username']
                ];
            }

            public function includeTheUserApiToken($item) {
                return $this->item($item['user_api_token'], $this->tokenTransformer, 'user_api_token');
            }
        };

        $transformer->tokenTransformer = new class extends AbstractTransformer {
            public function transform($item) {
                return [
                    'token' => $item['token']
                ];
            }
        };

        $user = [
            'username' => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456'
            ]
        ];

        /* assert */
        $smokescreen = (new Smokescreen)->parseIncludes('user_api_token')->item($user, $transformer);

        $this->assertEquals($user, $smokescreen->toArray());
    }
}
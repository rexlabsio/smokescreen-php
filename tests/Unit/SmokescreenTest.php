<?php
/**
 * smokescreen
 *
 * User: rhys
 * Date: 7/1/18
 * Time: 4:15 PM
 */

namespace RexSoftware\Smokescreen\Tests\Unit;

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

    /** @test */
    public function can_output_object()
    {
        $user = [
            'username' => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456'
            ]
        ];

        /* assert */
        $smokescreen = (new Smokescreen)
            ->item($user);

        $userObj = $smokescreen->toObject();
        $this->assertInstanceOf(\stdClass::class, $userObj);
        $this->assertObjectHasAttribute('username', $userObj);
        $this->assertObjectHasAttribute('user_api_token', $userObj);
        $this->assertObjectHasAttribute('token', $userObj->user_api_token);
        $this->assertEquals('phillip_j_fry', $userObj->username);
        $this->assertEquals('tkn_123456', $userObj->user_api_token->token);
//        $this->assertEquals($user, $smokescreen->toArray());
    }

    /** @test */
    public function transformer_should_not_be_required() {
        $user = [
            'username' => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456'
            ]
        ];

        /* assert */
        $smokescreen = (new Smokescreen)
            ->item($user);

        $this->assertEquals($user, $smokescreen->toArray());
    }
}
<?php
/**
 * smokescreen
 *
 * User: rhys
 * Date: 7/1/18
 * Time: 4:15 PM
 */

namespace Rexlabs\Smokescreen\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Serializer\DefaultSerializer;
use Rexlabs\Smokescreen\Smokescreen;
use Rexlabs\Smokescreen\Transformer\AbstractTransformer;

class SmokescreenTest extends TestCase
{
    /** @test */
    public function custom_include_method_used()
    {
        /* Test that a custom include method is called on the transformer */

        $transformer = new class extends AbstractTransformer
        {

            public $tokenTransformer;
            protected $includes = [
                'user_api_token' => 'method:includeTheUserApiToken',
            ];

            public function transform($item)
            {
                return [
                    'username' => $item['username'],
                ];
            }

            public function includeTheUserApiToken($item)
            {
                return $this->item($item['user_api_token'], $this->tokenTransformer, 'user_api_token');
            }
        };

        $transformer->tokenTransformer = new class extends AbstractTransformer
        {
            public function transform($item)
            {
                return [
                    'token' => $item['token'],
                ];
            }
        };

        $user = [
            'username' => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456',
            ],
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
                'token' => 'tkn_123456',
            ],
        ];

        /* assert */
        $smokescreen = (new Smokescreen)->item($user);

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
    public function transformer_should_not_be_required()
    {
        $user = [
            'username' => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456',
            ],
        ];

        /* assert */
        $smokescreen = (new Smokescreen)->item($user);

        $this->assertEquals($user, $smokescreen->toArray());
    }

    /** @test */
    public function resource_can_override_serializer()
    {
        $transformer = new class extends AbstractTransformer
        {

            protected $serializer;

            protected $includes = [
                'images',
            ];

            public function __construct()
            {
                $this->serializer = new class extends DefaultSerializer
                {
                    public function collection($resourceKey, array $data): array
                    {
                        return ['custom_serialize' => $data];
                    }
                };
            }

            public function transform($person)
            {
                return [
                    'id' => $person['id'],
                    'full_name' => "{$person['first_name']} {$person['last_name']}",
                ];
            }

            public function includeImages($item)
            {
                // In a real scenario we would fetch images off $item
                return $this->collection([
                    [
                        'id' => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id' => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ])->setSerializer($this->serializer);
            }
        };


        /* assert */
        $smokescreen = (new Smokescreen)->parseIncludes('images{url}')->item([
            'id' => '1234',
            'first_name' => 'Walter',
            'last_name' => 'Lilly',
        ], $transformer);

        $this->assertEquals([
            'id' => '1234',
            'full_name' => 'Walter Lilly',
            'images' => [
                'custom_serialize' => [
                    [
                        'id' => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id' => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ],
            ],
        ], $smokescreen->toArray());
    }

    /** @test */
    public function include_can_return_array_instead_of_resource()
    {
        $transformer = new class extends AbstractTransformer
        {
            protected $includes = [
                'images',
            ];

            public function transform($person)
            {
                return [
                    'id' => $person['id'],
                    'full_name' => "{$person['first_name']} {$person['last_name']}",
                ];
            }

            public function includeImages($item)
            {
                // Returning an array instead of a collection
                return [
                    [
                        'id' => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id' => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ];
            }
        };


        /* assert */
        $smokescreen = (new Smokescreen)->parseIncludes('images{url}')->item([
            'id' => '1234',
            'first_name' => 'Walter',
            'last_name' => 'Lilly',
        ], $transformer);

        $this->assertEquals([
            'id' => '1234',
            'full_name' => 'Walter Lilly',
            'images' => [
                [
                    'id' => 'image-1',
                    'url' => 'http://example.com/images/image-1',
                ],
                [
                    'id' => 'image-2',
                    'url' => 'http://example.com/images/image-2',
                ],
            ],
        ], $smokescreen->toArray());
    }
}
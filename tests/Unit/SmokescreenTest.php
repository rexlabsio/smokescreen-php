<?php

namespace Rexlabs\Smokescreen\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Exception\JsonEncodeException;
use Rexlabs\Smokescreen\Exception\MissingResourceException;
use Rexlabs\Smokescreen\Exception\UnhandledResourceTypeException;
use Rexlabs\Smokescreen\Includes\IncludeParserInterface;
use Rexlabs\Smokescreen\Includes\Includes;
use Rexlabs\Smokescreen\Relations\RelationLoaderInterface;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;
use Rexlabs\Smokescreen\Serializer\DefaultSerializer;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Smokescreen;
use Rexlabs\Smokescreen\Transformer\AbstractTransformer;
use Rexlabs\Smokescreen\Transformer\TransformerResolverInterface;
use stdClass;

class SmokescreenTest extends TestCase
{
    /**
     * @test
     */
    public function set_and_get_resource()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->item('data');
        $resource = $smokescreen->getResource();
        $this->assertInstanceOf(Item::class, $resource);

        $smokescreen->collection('data');
        $resource = $smokescreen->getResource();
        $this->assertInstanceOf(Collection::class, $resource);
    }

    /**
     * @test
     */
    public function can_manually_set_includes()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->setIncludes((new Includes())->add(['one', 'two']));
        $this->assertTrue($smokescreen->getIncludes()->hasKeys());
    }

    /**
     * @test
     */
    public function can_override_includes_parser()
    {
        $includeParser = new class() implements IncludeParserInterface {
            public function parse(string $str): Includes
            {
                return new Includes(['cheese']);
            }
        };

        $smokescreen = new Smokescreen();
        $smokescreen->setIncludeParser($includeParser);
        $this->assertInstanceOf(IncludeParserInterface::class, $includeParser);

        $smokescreen->parseIncludes('ignored');
        $this->assertEquals(['cheese'], $smokescreen->getIncludes()->keys());
    }

    /**
     * @test
     */
    public function apply_callback_to_collection()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->collection('data', null, null, function (Collection $resource) {
            $this->assertInstanceOf(Collection::class, $resource);
            $resource->setData(str_repeat($resource->getData(), 3));
        });
        $resource = $smokescreen->getResource();
        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertEquals('datadatadata', $resource->getData());
    }

    /**
     * @test
     */
    public function can_get_transformer_when_resource_is_set()
    {
        $transformer = $this->createTransformer();
        $smokescreen = new Smokescreen();
        $smokescreen->collection([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], $transformer);
        $this->assertEquals($transformer, $smokescreen->getTransformer());
    }

    /**
     * @test
     */
    public function cannot_get_transformer_without_resource()
    {
        $smokescreen = new Smokescreen();
        $this->expectException(MissingResourceException::class);
        $smokescreen->getTransformer();
    }

    /**
     * @test
     */
    public function transformer_will_be_null_when_not_defined_on_resource()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->collection([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);
        $this->assertNull($smokescreen->getTransformer());
    }

    /**
     * @test
     */
    public function can_set_transformer_on_resource()
    {
        $transformer = $this->createTransformer();
        $smokescreen = new Smokescreen();
        $smokescreen->collection([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);
        $smokescreen->setTransformer($transformer);
        $this->assertEquals($transformer, $smokescreen->getTransformer());
    }

    /**
     * @test
     */
    public function cannot_set_transformer_without_resource()
    {
        $smokescreen = new Smokescreen();
        $this->expectException(MissingResourceException::class);
        $smokescreen->setTransformer($this->createTransformer());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_set_item_transformer_to_closure()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->item([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], function ($item) {
            return [
                'id'         => 'xxx'.$item['id'],
                'first_name' => 'xxx'.$item['first_name'],
                'last_name'  => 'xxx'.$item['last_name'],
            ];
        });
        $this->assertInternalType('callable', $smokescreen->getTransformer());
        $this->assertEquals([
            'id'         => 'xxx1234',
            'first_name' => 'xxxJohn',
            'last_name'  => 'xxxDoe',
        ], $smokescreen->toArray());
    }

    /**
     * @test
     */
    public function cannot_set_invalid_transformer_on_resource()
    {
        $smokescreen = new Smokescreen();
        $this->expectException(InvalidTransformerException::class);
        $smokescreen->item([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], 'invalid_transformer');
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function invalid_json_throws_exception()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->item([
            'id'         => '1234',
            'first_name' => "John \xB1\x31", // Bad UTF8
            'last_name'  => 'Doe',
        ]);
        $this->expectException(JsonEncodeException::class);
        $smokescreen->toJson();
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function cannot_export_array_without_resource()
    {
        $smokescreen = new Smokescreen();
        $this->expectException(MissingResourceException::class);
        $smokescreen->toArray();
    }

    /**
     * Test that a custom include method is called on the transformer.
     *
     * @test
     *
     * @throws IncludeException
     */
    public function custom_include_method_used()
    {
        $transformer = new class() extends AbstractTransformer {
            public $tokenTransformer;
            protected $includes = [
                'user_api_token' => 'method:includeTheUserApiToken',
            ];

            public function transform($item): array
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

        $transformer->tokenTransformer = new class() extends AbstractTransformer {
            public function transform($item): array
            {
                return [
                    'token' => $item['token'],
                ];
            }
        };

        $user = [
            'username'       => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456',
            ],
        ];

        /* assert */
        $smokescreen = (new Smokescreen())->parseIncludes('user_api_token')->item($user, $transformer);

        $this->assertEquals($user, $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_output_object()
    {
        $user = [
            'username'       => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456',
            ],
        ];

        /* assert */
        $smokescreen = (new Smokescreen())->item($user);

        $userObj = $smokescreen->toObject();
        $this->assertObjectHasAttribute('username', $userObj);
        $this->assertObjectHasAttribute('user_api_token', $userObj);
        $this->assertObjectHasAttribute('token', $userObj->user_api_token);
        $this->assertEquals('phillip_j_fry', $userObj->username);
        $this->assertEquals('tkn_123456', $userObj->user_api_token->token);
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function transformer_should_not_be_required()
    {
        $user = [
            'username'       => 'phillip_j_fry',
            'user_api_token' => [
                'token' => 'tkn_123456',
            ],
        ];

        /* assert */
        $smokescreen = (new Smokescreen())->item($user);

        $this->assertEquals($user, $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function resource_can_override_serializer()
    {
        $transformer = new class() extends AbstractTransformer {
            protected $serializer;

            protected $includes = [
                'images',
            ];

            public function __construct()
            {
                $this->serializer = new class() extends DefaultSerializer {
                    public function collection($resourceKey, array $data): array
                    {
                        return ['custom_serialize' => $data];
                    }
                };
            }

            public function transform($person): array
            {
                return [
                    'id'        => $person['id'],
                    'full_name' => "{$person['first_name']} {$person['last_name']}",
                ];
            }

            public function includeImages()
            {
                // In a real scenario we would fetch images off $item
                return $this->collection([
                    [
                        'id'  => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id'  => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ])->setSerializer($this->serializer);
            }
        };

        /* assert */
        $smokescreen = (new Smokescreen())->parseIncludes('images{url}')->item([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], $transformer);

        $this->assertEquals([
            'id'        => '1234',
            'full_name' => 'John Doe',
            'images'    => [
                'custom_serialize' => [
                    [
                        'id'  => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id'  => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ],
            ],
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function include_can_return_array_instead_of_resource()
    {
        $transformer = new class() extends AbstractTransformer {
            protected $includes = [
                'images',
            ];

            public function transform($person): array
            {
                return [
                    'id'        => $person['id'],
                    'full_name' => "{$person['first_name']} {$person['last_name']}",
                ];
            }

            public function includeImages(): array
            {
                // Returning an array instead of a collection
                return [
                    [
                        'id'  => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id'  => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ];
            }
        };

        /* assert */
        $smokescreen = (new Smokescreen())->parseIncludes('images{url}')->item([
            'id'         => '1234',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], $transformer);

        $this->assertEquals([
            'id'        => '1234',
            'full_name' => 'John Doe',
            'images'    => [
                [
                    'id'  => 'image-1',
                    'url' => 'http://example.com/images/image-1',
                ],
                [
                    'id'  => 'image-2',
                    'url' => 'http://example.com/images/image-2',
                ],
            ],
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_serialize_an_object_with_to_array_method()
    {
        $objResource = new class() {
            public function toArray(): array
            {
                return [
                    'prop1' => 'val1',
                    'prop2' => 'val2',
                ];
            }
        };

        $smokescreen = new Smokescreen();
        $smokescreen->setResource($objResource);
        $this->assertEquals([
            'prop1' => 'val1',
            'prop2' => 'val2',
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function serialize_object_without_array_method_throws_exception()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->setResource(new stdClass());
        $this->expectException(UnhandledResourceTypeException::class);
        $smokescreen->toArray();
    }

    /**
     * @test
     *
     * @throws IncludeException
     *
     * @return void
     */
    public function serialize_string_throws_exception()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->setResource('Haha. Relax Bumblebee, I was just messing around.');
        $this->expectException(UnhandledResourceTypeException::class);
        $smokescreen->toArray();
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_serialize_collection_with_a_closure()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->collection([
            [
                'name' => 'Item 1',
            ],
            [
                'name' => 'Item 2',
            ],
        ]);
        $smokescreen->getResource()->setSerializer(function ($resourceKey, $data) {
            return ['custom' => $data];
        });
        $this->assertEquals([
            'custom' => [
                [
                    'name' => 'Item 1',
                ],
                [
                    'name' => 'Item 2',
                ],
            ],
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_serialize_item_with_a_closure()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->item(['name' => 'Item 1']);
        $smokescreen->getResource()->setSerializer(function ($resourceKey, $data) {
            return ['custom' => $data];
        });
        $this->assertEquals([
            'custom' => [
                'name' => 'Item 1',
            ],
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function setting_serializer_to_false_on_resource_disables_serialization()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->collection([
            [
                'name' => 'Item 1',
            ],
            [
                'name' => 'Item 2',
            ],
        ]);
        $smokescreen->getResource()->setSerializer(false);
        $this->assertEquals([
            [
                'name' => 'Item 1',
            ],
            [
                'name' => 'Item 2',
            ],
        ], $smokescreen->toArray());

        $smokescreen->item([
            'name' => 'Item 1',
        ]);
        $smokescreen->getResource()->setSerializer(false);
        $this->assertEquals([
            'name' => 'Item 1',
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function does_trigger_relationship_loading()
    {
        $transformer = new class() extends AbstractTransformer {
            protected $includes = [
                'owner'    => 'relation:users|default',
                'comments' => 'relation:comments',
            ];

            public function transform($item)
            {
                return $item;
            }
        };
        $smokescreen = new Smokescreen();
        $smokescreen->parseIncludes('owner,comments');
        $smokescreen->collection([
            [
                'id'    => 1,
                'title' => 'Example post',
                'body'  => 'Interesting post.',
            ],
            [
                'id'    => 2,
                'title' => 'Another post',
                'body'  => 'Not as interesting',
            ],
        ], $transformer);

        /**
         * @var RelationLoaderInterface|MockObject
         */
        $relationLoader = $this->getMockBuilder(RelationLoaderInterface::class)->setMethods(['load'])->getMock();

        $relationLoader
            ->expects($this->once())
            ->method('load')
            ->with($smokescreen->getResource());

        $smokescreen->setRelationLoader($relationLoader);
        $this->assertTrue($smokescreen->hasRelationLoader());
        $this->assertInstanceOf(RelationLoaderInterface::class, $smokescreen->getRelationLoader());
        $smokescreen->toArray();
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function only_default_props_are_returned()
    {
        $transformer = new class() extends AbstractTransformer {
            protected $defaultProps = [
                'id', // Only id returned
            ];

            public function transform($person): array
            {
                return [
                    'id'        => $person['id'],
                    'full_name' => "{$person['first_name']} {$person['last_name']}",
                ];
            }
        };

        $smokescreen = new Smokescreen();
        $smokescreen->item([
            'id'         => 123,
            'first_name' => 'Jerry',
            'last_name'  => 'Seinfeld',
        ], $transformer);
        $this->assertEquals([
            'id' => 123,
        ], $smokescreen->toArray());

        $smokescreen->parseIncludes('id,full_name');
        $this->assertEquals([
            'id'        => 123,
            'full_name' => 'Jerry Seinfeld',
        ], $smokescreen->toArray());
    }

    /**
     * @test
     */
    public function can_override_global_serializer()
    {
        $customSerializer = $this->createSerializer();

        $smokescreen = new Smokescreen();
        $smokescreen->setSerializer($customSerializer);
        $this->assertEquals($customSerializer, $smokescreen->getSerializer());
    }

    /**
     * @test
     */
    public function can_set_transformer_resolver()
    {
        $resolver = $this->createTransformerResolver();

        $smokescreen = new Smokescreen();
        $this->assertNull($smokescreen->getTransformerResolver());
        $smokescreen->setTransformerResolver($resolver);
        $this->assertEquals($resolver, $smokescreen->getTransformerResolver());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_autowire_include()
    {
        $resolver = $this->createTransformerResolver();
        $transformer = $this->createTransformer();
        $person = $this->createPersonArray();

        $smokescreen = new Smokescreen();
        $smokescreen->setTransformerResolver($resolver);
        $smokescreen->item($person, $transformer);
        $smokescreen->parseIncludes('parent');
        $this->assertEquals([
            'id'         => '234',
            'full_name'  => 'John Doe',
            'parent'     => [
                'id'         => 123,
                'first_name' => 'Mother',
                'last_name'  => 'Dearest',
            ],
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     *
     * @return void
     */
    public function null_item_transforms_to_empty_array()
    {
        $resolver = $this->createTransformerResolver();
        $transformer = $this->createTransformer();

        $smokescreen = new Smokescreen();
        $smokescreen->setTransformerResolver($resolver);
        $smokescreen->item(null, $transformer);
        $this->assertEquals([], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     *
     * @return void
     */
    public function null_collection_transforms_to_empty_array()
    {
        $resolver = $this->createTransformerResolver();
        $transformer = $this->createTransformer();

        $smokescreen = new Smokescreen();
        $smokescreen->setTransformerResolver($resolver);
        $smokescreen->collection(null, $transformer);
        $this->assertEquals([], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     *
     * @return void
     */
    public function null_item_can_include()
    {
        $smokescreen = new Smokescreen();
        $smokescreen->item(null, $this->createTransformer());
        $smokescreen->parseIncludes('images');
        $this->assertEquals([
            'images' => [
                [
                    'id'  => 'image-1',
                    'url' => 'http://example.com/images/image-1',
                ],
                [
                    'id'  => 'image-2',
                    'url' => 'http://example.com/images/image-2',
                ],
            ],
        ], $smokescreen->toArray());
    }

    /**
     * @test
     *
     * @throws IncludeException
     */
    public function can_autowire_include_with_object()
    {
        $resolver = $this->createTransformerResolver();
        $transformer = $this->createTransformer();
        $person = $this->createPersonObject();

        $smokescreen = new Smokescreen();
        $smokescreen->setTransformerResolver($resolver);
        $smokescreen->item($person, $transformer);
        $smokescreen->parseIncludes('parent,children');
        $this->assertEquals([
            'id'         => '234',
            'full_name'  => 'John Doe',
            'parent'     => [
                'id'         => 123,
                'first_name' => 'Mother',
                'last_name'  => 'Dearest',
            ],
            'children'     => [
                'data' => [
                    [
                        'id'         => 345,
                        'first_name' => 'LilJane',
                        'last_name'  => 'Doe',
                    ],
                    [
                        'id'         => 456,
                        'first_name' => 'LilJohn',
                        'last_name'  => 'Doe',
                    ],
                ],
            ],
        ], $smokescreen->toArray());
    }

    protected function createPersonObject(): stdClass
    {
        $parent = new stdClass();
        $parent->id = 123;
        $parent->first_name = 'Mother';
        $parent->last_name = 'Dearest';

        // Our person
        $person = new stdClass();
        $person->id = 234;
        $person->first_name = 'John';
        $person->last_name = 'Doe';

        $child1 = new stdClass();
        $child1->id = 345;
        $child1->first_name = 'LilJane';
        $child1->last_name = 'Doe';

        $child2 = new stdClass();
        $child2->id = 456;
        $child2->first_name = 'LilJohn';
        $child2->last_name = 'Doe';

        $person->parent = $parent;
        $person->children = [$child1, $child2];

        return $person;
    }

    protected function createPersonArray(): array
    {
        return (array) $this->createPersonObject();
    }

    protected function createTransformer(): AbstractTransformer
    {
        return new class() extends AbstractTransformer {
            protected $includes = [
                'images',
                'parent',
                'children' => 'collection',
            ];

            public function transform($person): array
            {
                // Handle both array and object for the purpose of our tests
                if (is_array($person)) {
                    return [
                        'id'        => $person['id'],
                        'full_name' => "{$person['first_name']} {$person['last_name']}",
                    ];
                }

                return [
                    'id'        => $person->id,
                    'full_name' => "{$person->first_name} {$person->last_name}",
                ];
            }

            public function includeImages(): array
            {
                // Returning an array instead of a collection
                return [
                    [
                        'id'  => 'image-1',
                        'url' => 'http://example.com/images/image-1',
                    ],
                    [
                        'id'  => 'image-2',
                        'url' => 'http://example.com/images/image-2',
                    ],
                ];
            }
        };
    }

    protected function createSerializer(): SerializerInterface
    {
        return new class() extends DefaultSerializer {
            public function collection($resourceKey, array $data): array
            {
                return ['custom_serialize' => $data];
            }
        };
    }

    protected function createTransformerResolver(): TransformerResolverInterface
    {
        return new class() implements TransformerResolverInterface {
            public function resolve(ResourceInterface $resource)
            {
                // Return a closure as the resolved transformer
                // which returns the exact item given to it
                return function ($item) {
                    return json_decode(json_encode($item), true);
                };
            }
        };
    }
}

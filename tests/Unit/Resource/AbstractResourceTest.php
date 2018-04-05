<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Resource;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Exception\InvalidSerializerException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;
use Rexlabs\Smokescreen\Resource\AbstractResource;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\AbstractTransformer;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;

class AbstractResourceTest extends TestCase
{
    /** @test */
    public function can_set_transformer_to_class()
    {
        $resource = $this->createAbstractResource();
        $transformer = new class() extends AbstractTransformer {
            public function transform($item)
            {
                return $item;
            }
        };

        $resource->setTransformer($transformer);
        $this->assertTrue($resource->hasTransformer());
        $this->assertInstanceOf(TransformerInterface::class, $resource->getTransformer());
    }

    /** @test */
    public function can_set_transformer_to_callable()
    {
        $resource = new Item();

        $resource->setTransformer(function ($item) {
            return $item;
        });
        $this->assertTrue(\is_callable($resource->getTransformer()));
    }

    /** @test */
    public function can_set_transformer_to_null()
    {
        $item = new Item();

        $item->setTransformer(null);
        $this->assertNull($item->getTransformer());
    }

    /** @test */
    public function can_set_data()
    {
        $collection = new Collection();

        $collection->setData('test');
        $this->assertEquals('test', $collection->getData());
        $collection->setData(null);
        $this->assertEquals(null, $collection->getData());
        $collection->setData(['test1', 'test2']);
        $this->assertEquals(['test1', 'test2'], $collection->getData());
    }

    /** @test */
    public function can_set_meta()
    {
        $collection = new Collection();

        $collection->setMeta(['key1' => 'val1', 'key2' => 'val2']);
        $this->assertEquals(['key1' => 'val1', 'key2' => 'val2'], $collection->getMeta());
        $this->assertEquals('val2', $collection->getMetaValue('key2'));
        $collection->setMetaValue('key1', 'changed');
        $this->assertEquals('changed', $collection->getMetaValue('key1'));
    }

    /** @test */
    public function can_set_resource_key()
    {
        $collection = new Collection();

        $collection->setResourceKey('test');
        $this->assertEquals('test', $collection->getResourceKey());
        $collection->setResourceKey(null);
        $this->assertEquals(null, $collection->getResourceKey());
    }

    /** @test */
    public function can_declare_relationships()
    {
        $transformer = new class() extends AbstractTransformer {
            protected $includes = [
                'owner'    => 'relation:users',
                'comments' => 'relation:comments',
            ];

            public function transform($item)
            {
                return $item;
            }
        };

        $collection = new Collection([], $transformer);
        $this->assertEquals([
            'owner'    => ['users'],
            'comments' => ['comments'],
        ], $collection->getRelationships());
    }

    /** @test */
    public function can_set_serializer_to_class()
    {
        $serializer = $this->createSerializer();

        $resource = new Collection([]);
        $resource->setSerializer($serializer);
        $resource->hasSerializer();
        $this->assertInstanceOf(SerializerInterface::class, $resource->getSerializer());
    }

    /** @test */
    public function can_set_serializer_to_callable()
    {
        $resource = new Item();

        $resource->setSerializer(function ($item) {
            return $item;
        });
        $this->assertTrue(\is_callable($resource->getSerializer()));
    }

    /** @test */
    public function can_set_serializer_to_null()
    {
        $item = new Item();

        $item->setSerializer(null);
        $this->assertNull($item->getSerializer());
    }

    /** @test */
    public function cannot_set_invalid_serializer()
    {
        $item = new Item();

        $this->expectException(InvalidSerializerException::class);
        $item->setSerializer(new \stdClass());
    }

    protected function createAbstractResource()
    {
        return new class() extends AbstractResource {
        };
    }

    protected function createSerializer()
    {
        return new class() implements SerializerInterface {
            public function collection($resourceKey, array $data): array
            {
                return $data;
            }

            public function item($resourceKey, array $data): array
            {
                return $data;
            }

            public function null()
            {
            }

            public function paginator(PaginatorInterface $paginator)
            {
                return [];
            }

            public function cursor(CursorInterface $cursor): array
            {
                return [];
            }
        };
    }
}

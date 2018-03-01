<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Resource;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Exception\ArrayConversionException;
use Rexlabs\Smokescreen\Exception\NotIterableException;
use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;
use Rexlabs\Smokescreen\Resource\Collection;

class CollectionTest extends TestCase
{
    /** @test */
    public function can_set_paginator()
    {
        $paginator = new class() implements PaginatorInterface
        {
            public function getCurrentPage(): int
            {
                return 1;
            }

            public function getLastPage(): int
            {
                return 1;
            }

            public function getTotal(): int
            {
                return 10;
            }

            public function getCount(): int
            {
                return 10;
            }

            public function getPerPage(): int
            {
                return 15;
            }

            public function getUrl($page): string
            {
                return "http://localhost/$page";
            }
        };

        $collection = new Collection();
        $collection->setPaginator($paginator);
        $this->assertTrue($collection->hasPaginator());
        $this->assertInstanceOf(PaginatorInterface::class, $paginator);
    }

    /** @test */
    public function can_set_cursor()
    {
        $cursor = new class() implements CursorInterface
        {
            public function getCurrent()
            {
                return 'current';
            }

            public function getPrev()
            {
                return 'prev';
            }

            public function getNext()
            {
                return 'next';
            }

            public function getCount(): int
            {
                return 10;
            }
        };

        $collection = new Collection();
        $collection->setCursor($cursor);
        $this->assertTrue($collection->hasCursor());
        $this->assertInstanceOf(CursorInterface::class, $cursor);
    }

    /** @test */
    public function can_iterate_collection()
    {
        $data = [
            [
                'id'   => 1,
                'name' => 'Item 1',
            ],
            [
                'id'   => 2,
                'name' => 'Item 2',
            ],
        ];


        $collection = new Collection();

        // Pass plain array
        $collection->setData($data);
        $this->assertInstanceOf(\ArrayIterator::class, $collection->getIterator());


        // Pass in iterator
        $collection->setData(new \ArrayIterator($data));
        $this->assertInstanceOf(\ArrayIterator::class, $collection->getIterator());

        // Pass in iterator aggregate
        $aggregateIterator = new class($data) implements \IteratorAggregate
        {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function getIterator()
            {
                return new \ArrayIterator($this->data);
            }
        };
        $collection->setData($aggregateIterator);
        $this->assertInstanceOf(\ArrayIterator::class, $collection->getIterator());

        $collection->setData('not iterable');
        $this->expectException(NotIterableException::class);
        $collection->getIterator();
    }

    /** @test */
    public function can_convert_collection_to_array()
    {
        $data = [
            [
                'id'   => 1,
                'name' => 'Item 1',
            ],
            [
                'id'   => 2,
                'name' => 'Item 2',
            ],
        ];


        $collection = new Collection();

        // Pass plain array
        $collection->setData($data);
        $this->assertEquals($data, $collection->toArray());

        // Pass in iterator
        $collection->setData(new \ArrayIterator($data));
        $this->assertEquals($data, $collection->toArray());

        // Pass in iterator aggregate
        $aggregateIterator = new class($data) implements \IteratorAggregate
        {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function getIterator()
            {
                return new \ArrayIterator($this->data);
            }
        };
        $collection->setData($aggregateIterator);
        $this->assertEquals($data, $collection->toArray());

        $collection->setData('not array');
        $this->expectException(ArrayConversionException::class);
        $collection->toArray();
    }
}
<?php

namespace Rexlabs\Smokescreen\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;
use Rexlabs\Smokescreen\Serializer\SimpleCollectionSerializer;

class SimpleCollectionSerializerTest extends TestCase
{
    /** @test */
    public function can_serialize_collection_without_data_property()
    {
        $data = [
            [
                'id'    => 1,
                'title' => 'Pride and Prejudice',
            ],
            [
                'id'    => 2,
                'title' => '1982',
            ],
        ];

        $serializer = new SimpleCollectionSerializer();

        $this->assertEquals($data, $serializer->collection(null, $data));
    }

    /** @test */
    public function can_serialize_item()
    {
        $data = [
            'id'    => 1,
            'title' => 'Pride and Prejudice',
        ];

        $serializer = new SimpleCollectionSerializer();

        $this->assertEquals($data, $serializer->item(null, $data));
    }

    /** @test */
    public function can_serialize_null()
    {
        $serializer = new SimpleCollectionSerializer();

        $this->assertEquals([], $serializer->nullCollection());
        $this->assertEquals(null, $serializer->nullItem());
    }

    /** @test */
    public function serializes_an_empty_paginator()
    {
        $serializer = new SimpleCollectionSerializer;
        $paginator = $this->createPaginator();

        $this->assertEquals([], $serializer->paginator($paginator));
    }

    /** @test */
    public function serializes_an_empty_cursor()
    {
        $serializer = new SimpleCollectionSerializer();
        $cursor = $this->createCursor();

        $this->assertEquals([], $serializer->cursor($cursor));
    }

    /**
     * @return CursorInterface
     */
    public function createCursor(): CursorInterface
    {
        return new class() implements CursorInterface {
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
    }

    /**
     * @param int $total
     * @param int $currentPage
     *
     * @return PaginatorInterface
     */
    public function createPaginator(int $total = 10, int $currentPage = 1): PaginatorInterface
    {
        return new class($total, $currentPage) implements PaginatorInterface {
            protected $perPage = 15;
            protected $total;
            protected $currentPage;

            public function __construct($total, $currentPage)
            {
                $this->total = $total;
                $this->currentPage = $currentPage;
            }

            public function getCurrentPage(): int
            {
                return $this->currentPage;
            }

            public function getLastPage(): int
            {
                return ceil($this->total / $this->perPage);
            }

            public function getTotal(): int
            {
                return $this->total;
            }

            public function getCount(): int
            {
                $offset = ($this->currentPage - 1) * $this->perPage;
                $remainder = $this->total - $offset;

                return $remainder < $this->perPage ? $remainder : $this->perPage;
            }

            public function getPerPage(): int
            {
                return $this->perPage;
            }

            public function getUrl($page): string
            {
                return "http://localhost/$page";
            }
        };
    }
}

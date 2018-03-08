<?php

namespace Rexlabs\Smokescreen\Serializer;

use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;

/**
 * The default serializer:
 * - Returns collections nested under a "data" key
 * - Returns items without any nesting.
 */
class DefaultSerializer implements SerializerInterface
{
    /**
     * Serialize a collection.
     * The data will be nested under a "data" key.
     *
     * @param string $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data): array
    {
        return ['data' => $data];
    }

    /**
     * Serialize an item.
     * The item data will be returned as-is. (not nested).
     *
     * @param string $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function item($resourceKey, array $data): array
    {
        return $data;
    }

    /**
     * Serialize null resource.
     * ¯\_(ツ)_/¯.
     *
     * @return array
     */
    public function null()
    {
        return [];
    }

    /**
     * Serialize the paginator.
     *
     * @param PaginatorInterface $paginator
     *
     * @return array
     */
    public function paginator(PaginatorInterface $paginator)
    {
        $currentPage = $paginator->getCurrentPage();
        $lastPage = $paginator->getLastPage();

        $pagination = [
            'total'        => $paginator->getTotal(),
            'count'        => $paginator->getCount(),
            'per_page'     => $paginator->getPerPage(),
            'current_page' => $currentPage,
            'total_pages'  => $lastPage,
        ];

        $pagination['links'] = [];

        if ($currentPage > 1) {
            $pagination['links']['previous'] = $paginator->getUrl($currentPage - 1);
        }

        if ($currentPage < $lastPage) {
            $pagination['links']['next'] = $paginator->getUrl($currentPage + 1);
        }

        return ['pagination' => $pagination];
    }

    /**
     * Serialize the cursor.
     *
     * @param CursorInterface $cursor
     *
     * @return array
     */
    public function cursor(CursorInterface $cursor): array
    {
        return [
            'cursor' => [
                'current' => $cursor->getCurrent(),
                'prev'    => $cursor->getPrev(),
                'next'    => $cursor->getNext(),
                'count'   => (int) $cursor->getCount(),
            ],
        ];
    }
}

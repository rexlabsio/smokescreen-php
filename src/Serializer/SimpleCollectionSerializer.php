<?php

namespace Rexlabs\Smokescreen\Serializer;

use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;

/**
 * Serializer to return simple collections:
 * - Returns collections without nesting them under a "data" key
 * - Returns items without any nesting.
 * - Does not support pagination
 * - Does not support cursor
 */
class SimpleCollectionSerializer extends DefaultSerializer
{
    /**
     * Serialize a collection.
     * The data will NOT be nested under a "data" key.
     *
     * @param string $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data): array
    {
        return $data;
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
        return [];
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
        return [];
    }
}

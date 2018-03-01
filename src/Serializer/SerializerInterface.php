<?php
namespace Rexlabs\Smokescreen\Serializer;

use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;

interface SerializerInterface
{
    /**
     * Serialize a collection.
     *
     * @param string $resourceKey
     * @param array $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data): array;

    /**
     * Serialize an item.
     *
     * @param string $resourceKey
     * @param array $data
     *
     * @return array
     */
    public function item($resourceKey, array $data): array;

    /**
     * Serialize null resource.
     *
     * @return mixed
     */
    public function null();

    // TODO: Add meta

    /**
     * Serialize a paginator.
     *
     * @param PaginatorInterface $paginator
     * @return mixed
     */
    public function paginator(PaginatorInterface $paginator);

    /**
     * Serialize a cursor.
     *
     * @param CursorInterface $cursor
     * @return array
     */
    public function cursor(CursorInterface $cursor): array;
}
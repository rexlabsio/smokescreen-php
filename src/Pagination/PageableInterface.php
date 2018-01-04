<?php
namespace RexSoftware\Smokescreen\Pagination;


interface PageableInterface
{
    /**
     * Get the paginator instance.
     *
     * @return PaginatorInterface|null
     */
    public function getPaginator();

    /**
     * Determine if the resource has a paginator implementation.
     *
     * @return bool
     */
    public function hasPaginator(): bool;

    /**
     * Get the cursor instance.
     *
     * @return CursorInterface|null
     */
    public function getCursor();

    /**
     * Determine if the resource has a cursor implementation.
     *
     * @return bool
     */
    public function hasCursor(): bool;

    /**
     * Set the paginator instance.
     *
     * @param PaginatorInterface $paginator
     *
     * @return $this
     */
    public function setPaginator(PaginatorInterface $paginator);

    /**
     * Set the cursor instance.
     *
     * @param CursorInterface $cursor
     *
     * @return $this
     */
    public function setCursor(CursorInterface $cursor);
}
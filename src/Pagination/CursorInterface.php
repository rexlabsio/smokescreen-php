<?php

namespace Rexlabs\Smokescreen\Pagination;

interface CursorInterface
{
    /**
     * Get the current cursor value.
     *
     * @return mixed
     */
    public function getCurrent();

    /**
     * Get the prev cursor value.
     *
     * @return mixed
     */
    public function getPrev();

    /**
     * Get the next cursor value.
     *
     * @return mixed
     */
    public function getNext();

    /**
     * Returns the total items in the current cursor.
     *
     * @return int
     */
    public function getCount(): int;
}

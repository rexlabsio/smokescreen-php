<?php

namespace Rexlabs\Smokescreen\Resource;

use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PageableInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;

class Collection extends AbstractResource implements PageableInterface, \IteratorAggregate
{
    /** @var array|\ArrayIterator */
    protected $data;

    /** @var PaginatorInterface */
    protected $paginator;

    /** @var  CursorInterface */
    protected $cursor;

    public function getPaginator()
    {
        return $this->paginator;
    }

    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;

        return $this;
    }

    public function hasPaginator(): bool
    {
        return $this->paginator instanceof PaginatorInterface;
    }

    public function getCursor()
    {
        return $this->cursor;
    }

    public function setCursor(CursorInterface $cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    public function hasCursor(): bool
    {
        return $this->cursor instanceof CursorInterface;
    }

    public function getIterator()
    {
        if ($this->data instanceof \ArrayIterator) {
            return $this->data;
        }

        if ($this->data instanceof \IteratorAggregate) {
            return $this->data->getIterator();
        }

        return new \ArrayIterator($this->data);
    }

    public function toArray()
    {
        if (\is_array($this->data)) {
            return $this->data;
        }

        if ($this->data instanceof \ArrayIterator) {
            return iterator_to_array($this->data, false);
        }

        if ($this->data instanceof \IteratorAggregate) {
            return iterator_to_array($this->data->getIterator(), false);
        }

        throw new \RuntimeException('Cannot convert data to array');
    }
}
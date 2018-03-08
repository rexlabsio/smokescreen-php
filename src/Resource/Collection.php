<?php

namespace Rexlabs\Smokescreen\Resource;

use Rexlabs\Smokescreen\Exception\ArrayConversionException;
use Rexlabs\Smokescreen\Exception\NotIterableException;
use Rexlabs\Smokescreen\Pagination\CursorInterface;
use Rexlabs\Smokescreen\Pagination\PageableInterface;
use Rexlabs\Smokescreen\Pagination\PaginatorInterface;

class Collection extends AbstractResource implements PageableInterface, \IteratorAggregate
{
    /** @var PaginatorInterface */
    protected $paginator;

    /** @var CursorInterface */
    protected $cursor;

    /**
     * {@inheritdoc}
     */
    public function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->cursor = null;
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPaginator(): bool
    {
        return $this->paginator instanceof PaginatorInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function setCursor(CursorInterface $cursor)
    {
        $this->paginator = null;
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCursor(): bool
    {
        return $this->cursor instanceof CursorInterface;
    }

    /**
     * Returns an Iterator (to implement the ArrayIterator) interface for
     * easily traversing a collection.
     *
     * @throws \Rexlabs\Smokescreen\Exception\NotIterableException
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        if ($this->data instanceof \ArrayIterator) {
            return $this->data;
        }

        if ($this->data instanceof \IteratorAggregate) {
            return $this->data->getIterator();
        }

        if (!\is_array($this->data)) {
            throw new NotIterableException('Cannot get iterator for data');
        }

        return new \ArrayIterator($this->data);
    }

    /**
     * Converts the Collection data to an array.
     *
     * @throws \Rexlabs\Smokescreen\Exception\ArrayConversionException
     *
     * @return array|\ArrayIterator|mixed|null
     */
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

        throw new ArrayConversionException('Cannot convert data to array');
    }
}

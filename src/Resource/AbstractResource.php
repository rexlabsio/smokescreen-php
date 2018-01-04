<?php

namespace RexSoftware\Smokescreen\Resource;

use RexSoftware\Smokescreen\Exception\InvalidTransformerException;
use RexSoftware\Smokescreen\Transformer\TransformerInterface;

abstract class AbstractResource implements ResourceInterface
{
    /**
     * The data to process with the transformer.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Array of meta data.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * The resource key.
     *
     * @var string
     */
    protected $resourceKey;

    /**
     * A transformer or callable to process the data attached to this resource.
     *
     * @var callable|TransformerInterface|null
     */
    protected $transformer;

    /**
     * Create a new resource instance.
     *
     * @param mixed $data
     * @param callable|TransformerInterface|null $transformer
     * @param string $resourceKey
     */
    public function __construct($data = null, $transformer = null, $resourceKey = null)
    {
        $this->data = $data;
        $this->transformer = $transformer;
        $this->resourceKey = $resourceKey;
    }

    /**
     * Get the data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data.
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the meta data.
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Set the meta data.
     *
     * @param array $meta
     *
     * @return $this
     */
    public function setMeta(array $meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get the meta data.
     *
     * @param string $metaKey
     *
     * @return array
     */
    public function getMetaValue($metaKey)
    {
        return $this->meta[$metaKey];
    }

    /**
     * Get the resource key.
     *
     * @return string
     */
    public function getResourceKey()
    {
        return $this->resourceKey;
    }

    /**
     * Set the resource key.
     *
     * @param string $resourceKey
     *
     * @return $this
     */
    public function setResourceKey(string $resourceKey)
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    /**
     * Set the meta data.
     *
     * @param string $metaKey
     * @param mixed $metaValue
     *
     * @return $this
     */
    public function setMetaValue(string $metaKey, $metaValue)
    {
        $this->meta[$metaKey] = $metaValue;

        return $this;
    }

    /**
     * Get a list of relationships from the transformer
     * Only applicable when the transformer is an instance of the TransformerInterface (eg. AbstractTransformer)
     * @return array
     */
    public function getRelationships(): array
    {
        $relationships = [];
        if ($this->hasTransformer()) {
            if (($transformer = $this->getTransformer()) instanceof TransformerInterface) {
                $relationships = $transformer->getRelationships();
            }
        }

        return $relationships;
    }

    /**
     * @return boolean
     */
    public function hasTransformer(): bool
    {
        return $this->transformer !== null;
    }

    /**
     * Get the transformer.
     *
     * @return callable|TransformerInterface|null
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * Set the transformer.
     *
     * @param callable|TransformerInterface $transformer
     *
     * @return $this
     * @throws InvalidTransformerException
     */
    public function setTransformer($transformer)
    {
        if (!$transformer instanceof TransformerInterface && !is_callable($transformer, true)) {
            throw new InvalidTransformerException('Transformer must be a callable or implement TransformerInterface');
        }
        $this->transformer = $transformer;

        return $this;
    }
}
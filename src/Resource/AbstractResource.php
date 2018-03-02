<?php

namespace Rexlabs\Smokescreen\Resource;

use Rexlabs\Smokescreen\Exception\InvalidSerializerException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;

abstract class AbstractResource implements ResourceInterface
{
    /**
     * The data to process with the transformer.
     *
     * @var array|\ArrayIterator|mixed
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
     * Provide a custom serializer for this resource.
     *
     * @var callable|SerializerInterface|null
     */
    protected $serializer;

    /**
     * Create a new resource instance.
     *
     * @param mixed                              $data
     * @param callable|TransformerInterface|null $transformer
     * @param string                             $resourceKey
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     */
    public function __construct($data = null, $transformer = null, $resourceKey = null)
    {
        $this->setData($data);
        $this->setTransformer($transformer);
        $this->setResourceKey($resourceKey);
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
     * @return string|null
     */
    public function getResourceKey()
    {
        return $this->resourceKey;
    }

    /**
     * Set the resource key.
     *
     * @param string|null $resourceKey
     *
     * @return $this
     */
    public function setResourceKey($resourceKey)
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    /**
     * Set the meta data.
     *
     * @param string $metaKey
     * @param mixed  $metaValue
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
     * @param callable|TransformerInterface|null $transformer
     *
     * @return $this
     * @throws InvalidTransformerException
     */
    public function setTransformer($transformer)
    {
        if ($transformer !== null) {
            if (!$this->isValidTransformer($transformer)) {
                throw new InvalidTransformerException('Transformer must be a callable or implement TransformerInterface');
            }
        }

        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Determines if the given argument is a valid transformer.
     * @param mixed $transformer
     *
     * @return bool
     */
    public function isValidTransformer($transformer): bool
    {
        if ($transformer instanceof TransformerInterface && method_exists($transformer, 'transform')) {
            return true;
        }

        if (\is_callable($transformer)) {
            return true;
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function hasSerializer(): bool
    {
        return $this->serializer !== null;
    }

    /**
     * @return callable|SerializerInterface|false|null
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Sets a custom serializer to be used for this resource
     * - Usually you will set this to an instance of SerializerInterface.
     * - Set to false to force no serialization on this resource.
     * - When set to null the default serializer will be used.
     * - Optionally set a closure/callback to be used for serialization.
     *
     * @param callable|SerializerInterface|false|null $serializer
     * @throws InvalidSerializerException
     * @return $this
     */
    public function setSerializer($serializer)
    {
        if ($serializer !== null) {
            if (!$this->isValidSerializer($serializer)) {
                throw new InvalidSerializerException('Serializer must be one of: callable, SerializerInterface, false or null');
            }
        }
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @param mixed $serializer
     *
     * @return bool
     */
    public function isValidSerializer($serializer): bool
    {
        if ($serializer === false) {
            // False is a valid value (forces serialization to be off)
            return true;
        }
        if ($serializer instanceof SerializerInterface) {
            return true;
        }
        if (\is_callable($serializer)) {
            return true;
        }

        return false;
    }
}
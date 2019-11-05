<?php

namespace Rexlabs\Smokescreen\Resource;

use ArrayIterator;
use Rexlabs\Smokescreen\Compositor\CompositorInterface;
use Rexlabs\Smokescreen\Exception\InvalidCompositorException;
use Rexlabs\Smokescreen\Exception\InvalidSerializerException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;
use function is_callable;

abstract class AbstractResource implements ResourceInterface
{
    /**
     * The data to process with the transformer.
     *
     * @var array|ArrayIterator|mixed
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
     * @var callable|SerializerInterface|false|null
     */
    protected $serializer;

    /**
     * @var callable|CompositorInterface|null
     */
    protected $compositor;

    /**
     * Create a new resource instance.
     *
     * @param mixed                              $data
     * @param callable|TransformerInterface|null $transformer
     * @param string                             $resourceKey
     *
     * @throws InvalidTransformerException
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
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the meta data.
     *
     * @return array
     */
    public function getMeta(): array
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
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get the meta data.
     *
     * @param string $metaKey
     *
     * @return array|mixed
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
    public function setResourceKey($resourceKey): self
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
    public function setMetaValue(string $metaKey, $metaValue): self
    {
        $this->meta[$metaKey] = $metaValue;

        return $this;
    }

    /**
     * Get a list of relationships from the transformer
     * Only applicable when the transformer is an instance of the TransformerInterface (eg. AbstractTransformer).
     *
     * @return array
     */
    public function getRelationships(): array
    {
        $transformer = $this->getTransformer();
        return $transformer instanceof TransformerInterface
            ? $transformer->getRelationships()
            : [];
    }

    /**
     * @return bool
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
     * @throws InvalidTransformerException
     *
     * @return $this
     */
    public function setTransformer($transformer): self
    {
        if ($transformer !== null && !$this->isValidTransformer($transformer)) {
            throw new InvalidTransformerException();
        }

        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Determines if the given argument is a valid transformer.
     *
     * @param mixed $transformer
     *
     * @return bool
     */
    public function isValidTransformer($transformer): bool
    {
        if ($transformer instanceof TransformerInterface) {
            return true;
        }

        if (is_callable($transformer)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasSerializer(): bool
    {
        return $this->serializer !== null;
    }

    /**
     * @return SerializerInterface|callable|false|null
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
     * @param SerializerInterface|callable|false|null $serializer
     *
     * @throws InvalidSerializerException
     *
     * @return $this
     */
    public function setSerializer($serializer): self
    {
        if ($serializer !== null && !$this->isValidSerializer($serializer)) {
            throw new InvalidSerializerException();
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
        if (is_callable($serializer)) {
            return true;
        }

        return false;
    }

    /**
     * Sets a custom compositor to be used for this resource
     * - Usually you will set this to an instance of CompositorInterface.
     * - When set to null the compositor of the pipeline will be used.
     * - Optionally set a closure/callback to be used for composition.
     *
     * @param CompositorInterface|callable|null $compositor
     *
     * @throws InvalidCompositorException
     *
     * @return $this
     */
    public function setCompositor($compositor): self
    {
        if ($compositor !== null && !$this->isValidCompositor($compositor)) {
            throw new InvalidCompositorException('Compositor must be one of: callable, CompositorInterface or null');
        }
        $this->compositor = $compositor;

        return $this;
    }

    /**
     * @param mixed $compositor
     *
     * @return bool
     */
    public function isValidCompositor($compositor): bool
    {
        return $compositor instanceof SerializerInterface || is_callable($compositor);
    }

    /**
     * @return bool
     */
    public function hasCompositor(): bool
    {
        return $this->compositor !== null;
    }

    /**
     * @return CompositorInterface|callable|null
     */
    public function getCompositor()
    {
        return $this->compositor;
    }
}

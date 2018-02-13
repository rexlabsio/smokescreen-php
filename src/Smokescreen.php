<?php
namespace RexSoftware\Smokescreen;

use RexSoftware\Smokescreen\Exception\InvalidTransformerException;
use RexSoftware\Smokescreen\Exception\JsonEncodeException;
use RexSoftware\Smokescreen\Exception\MissingResourceException;
use RexSoftware\Smokescreen\Exception\UnhandledResourceType;
use RexSoftware\Smokescreen\Includes\IncludeParser;
use RexSoftware\Smokescreen\Includes\IncludeParserInterface;
use RexSoftware\Smokescreen\Includes\Includes;
use RexSoftware\Smokescreen\Relations\RelationLoaderInterface;
use RexSoftware\Smokescreen\Resource\Collection;
use RexSoftware\Smokescreen\Resource\Item;
use RexSoftware\Smokescreen\Resource\ResourceInterface;
use RexSoftware\Smokescreen\Serializer\DefaultSerializer;
use RexSoftware\Smokescreen\Serializer\SerializerInterface;
use RexSoftware\Smokescreen\Transformer\TransformerInterface;

/**
 * Smokescreen is a library for transforming and serializing data - typically RESTful API output.
 * @package RexSoftware\Smokescreen
 */
class Smokescreen implements \JsonSerializable
{
    /** @var ResourceInterface Item or Collection to be transformed */
    protected $resource;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var IncludeParserInterface */
    protected $includeParser;

    /** @var RelationLoaderInterface */
    protected $relationLoader;

    /** @var Includes */
    protected $includes;

    /**
     * Return the current resource
     * @return ResourceInterface|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set the resource item to be transformed
     * @param mixed                     $data
     * @param TransformerInterface|null $transformer
     * @param null                      $key
     * @return $this
     */
    public function item($data, TransformerInterface $transformer = null, $key = null)
    {
        $this->resource = new Item($data, $transformer, $key);

        return $this;
    }

    /**
     * Set the resource collection to be transformed
     * @param mixed                     $data
     * @param TransformerInterface|null $transformer
     * @param string|null               $key
     * @param callable|null             $callback
     * @return $this
     */
    public function collection($data, TransformerInterface $transformer = null, $key = null, callable $callback = null)
    {
        $this->resource = new Collection($data, $transformer, $key);
        if ($callback !== null) {
            $callback($this->resource);
        }

        return $this;
    }

    /**
     * Sets the transformer to be used to transform the resource ... later
     * @return callable|null|TransformerInterface
     * @throws MissingResourceException
     */
    public function getTransformer()
    {
        if (!$this->resource) {
            throw new MissingResourceException('Resource must be specified before setting a transformer');
        }

        return $this->resource->getTransformer();
    }

    /**
     * Sets the transformer to be used to transform the resource ... later
     * @param TransformerInterface|null $transformer
     * @return $this
     * @throws MissingResourceException
     */
    public function setTransformer(TransformerInterface $transformer = null)
    {
        if (!$this->resource) {
            throw new MissingResourceException('Resource must be specified before setting a transformer');
        }
        $this->resource->setTransformer($transformer);

        return $this;
    }

    /**
     * Returns an object representation of the transformed/serialized data.
     * @return \stdClass
     * @throws \RexSoftware\Smokescreen\Exception\JsonEncodeException
     */
    public function toObject()
    {
        return json_decode($this->toJson(), false);
    }

    /**
     * Outputs a JSON string of the resulting transformed and serialized data.
     * @param int $options
     * @return string
     * @throws \RexSoftware\Smokescreen\Exception\JsonEncodeException
     */
    public function toJson($options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonEncodeException(json_last_error_msg());
        }

        return $json;
    }

    /**
     * Output the transformed and serialized data as an array.
     * Implements PHP's JsonSerializable interface.
     * @return array
     * @see Smokescreen::toArray()
     * @throws \RexSoftware\Smokescreen\Exception\InvalidTransformerException
     * @throws \RexSoftware\Smokescreen\Exception\MissingResourceException
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Return the transformed data as an array
     * @return array
     * @throws \RexSoftware\Smokescreen\Exception\InvalidTransformerException
     * @throws \RexSoftware\Smokescreen\Exception\MissingResourceException
     */
    public function toArray(): array
    {
        if (!$this->resource) {
            throw new MissingResourceException('No resource has been defined to transform');
        }

        // Kick of serialization of the resource
        return $this->serializeResource($this->resource, $this->getIncludes());
    }

    /**
     * @param ResourceInterface|mixed $resource
     * @param Includes                $includes
     * @return array
     * @throws \RexSoftware\Smokescreen\Exception\UnhandledResourceType
     * @throws \RexSoftware\Smokescreen\Exception\InvalidTransformerException
     */
    protected function serializeResource($resource, Includes $includes): array
    {
        // Relationship loading
        if ($resource instanceof ResourceInterface) {
            $this->loadRelations($resource);
        }

        // Build the output by recursively transforming each resource
        $output = [];
        if ($resource instanceof Collection) {
            $output = $this->serializeCollection($resource, $includes);
        } elseif ($resource instanceof Item) {
            $output = $this->serializeItem($resource, $includes);
        } elseif (\is_array($resource)) {
            $output = $resource;
        } elseif (\is_object($resource) && method_exists($resource, 'toArray')) {
            $output = $resource->toArray();
        } else {
            throw new UnhandledResourceType('Unable to serialize resource of type ' . \gettype($resource));
        }

        return $output;
    }

    /**
     * Fire the relation loader (if defined) for this resource
     * @param ResourceInterface $resource
     */
    protected function loadRelations(ResourceInterface $resource)
    {
        if ($this->relationLoader !== null) {
            $this->relationLoader->load($resource);
        }
    }

    /**
     * @param Collection $collection
     * @param Includes   $includes
     * @return array
     * @throws InvalidTransformerException
     */
    protected function serializeCollection(Collection $collection, Includes $includes): array
    {
        // Get the globally set serializer (resource may override)
        $defaultSerializer = $this->getSerializer();

        // Collection resources implement IteratorAggregate ... so that's nice
        $items = [];
        foreach ($collection as $item) {
            // $item might be a Model or an array etc.
            $items[] = $this->transformItem($item, $collection->getTransformer(), $includes);
        }

        // The collection can have a custom serializer defined
        $serializer = $collection->getSerializer() ?? $defaultSerializer;
        $isSerializerInterface = $serializer instanceof SerializerInterface;

        if ($isSerializerInterface) {
            // Serialize via object implementing SerializerInterface
            $output = $serializer->collection($collection->getResourceKey(), $items);
        } elseif (\is_callable($serializer)) {
            // Serialize via a callable/closure
            $output = $serializer($collection->getResourceKey(), $items);
        } else {
            // No serialization
            $output = $items;
        }

        if ($isSerializerInterface && $collection->hasPaginator()) {
            $output = array_merge($output, $serializer->paginator($collection->getPaginator()));
        }

        return $output;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer ?? new DefaultSerializer();
    }

    /**
     * Set the serializer which will be used to output the transformed resource
     * @param SerializerInterface|null $serializer
     * @return $this
     */
    public function setSerializer(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @param mixed                                    $item
     * @param mixed|TransformerInterface|callable|null $transformer
     * @param Includes                                 $includes
     * @return array
     * @throws \RexSoftware\Smokescreen\Exception\InvalidTransformerException
     */
    protected function transformItem($item, $transformer, Includes $includes): array
    {
        if ($transformer === null) {
            // No transformation can be applied
            return (array)$item;
        }
        if (\is_callable($transformer)) {
            // Callable should simply return an array
            return (array)$transformer($item);
        }

        // Otherwise, we expect a transformer object
        if (!$transformer instanceof TransformerInterface) {
            throw new InvalidTransformerException('Transformer must implement TransformerInterface');
        }

        // We need to double-check it has a transform() method - perhaps this could be done on set?
        // Since PHP doesn't support contravariance, we can't define the transform() signature on the interface
        if (!method_exists($transformer, 'transform')) {
            throw new InvalidTransformerException('Transformer must provide a transform() method');
        }

        // Only these keys may be mapped
        $availableIncludeKeys = $transformer->getAvailableIncludes();

        // Wanted includes is a either the explicit includes requested, or the defaults for the transformer.
        $wantIncludeKeys = $includes->baseKeys() ?: $transformer->getDefaultIncludes();

        // Find the keys that are declared in the $includes of the transformer
        $mappedIncludeKeys = array_filter($wantIncludeKeys, function ($includeKey) use ($availableIncludeKeys) {
            return \in_array($includeKey, $availableIncludeKeys, true);
        });

        // We can consider our props anything to not be mapped
        $filterProps = array_filter($wantIncludeKeys, function ($includeKey) use ($mappedIncludeKeys) {
            return !\in_array($includeKey, $mappedIncludeKeys, true);
        });

        // Were any filter props explicitly provided?
        // If not, see if defaults were provided from the transformer.
        if (empty($filterProps)) {
            // No explicit props provided
            $defaultProps = $transformer->getDefaultProps();
            if (!empty($defaultProps)) {
                $filterProps = $defaultProps;
            }
        }

        // Get the base data from the transformation
        $data = (array)$transformer->transform($item);

        // Filter the sparse field-set
        if (!empty($filterProps)) {
            $filteredData = array_filter($data, function ($key) use ($filterProps) {
                return \in_array($key, $filterProps, true);
            }, ARRAY_FILTER_USE_KEY);

            // We must always have some data after filtering
            // If our filtered data is empty, we should just ignore it
            if (!empty($filteredData)) {
                $data = $filteredData;
            }
        }

        // Add includes to the payload
        $includeMap = $transformer->getIncludeMap();
        foreach ($mappedIncludeKeys as $includeKey) {
            $resource = $this->executeTransformerInclude($transformer, $includeMap[$includeKey], $item);

            if ($resource instanceof ResourceInterface) {
                // Resource object
                $data[$resource->getResourceKey() ?: $includeKey] = !$resource->getData() ? null : $this->serializeResource($resource,
                    $includes->splice($includeKey));
            } else {
                // Plain old array
                $data[$includeKey] = $this->serializeResource($resource, $includes->splice($includeKey));
            }


        }

        return $data;
    }

    protected function executeTransformerInclude($transformer, $include, $item)
    {
        return \call_user_func([$transformer, $include['method']], $item);
    }

    /**
     * @param Item     $item
     * @param Includes $includes
     * @return array
     * @throws InvalidTransformerException
     */
    protected function serializeItem(Item $item, Includes $includes)
    {
        // Get the globally set serializer (resource may override)
        $defaultSerializer = $this->getSerializer();

        // The collection can have a custom serializer defined
        $serializer = $item->getSerializer() ?? $defaultSerializer;
        $isSerializerInterface = $serializer instanceof SerializerInterface;

        // Transform the item data
        $itemData = $this->transformItem($item->getData(), $item->getTransformer(), $includes);

        // Serialize the item data
        if ($isSerializerInterface) {
            // Serialize via object implementing SerializerInterface
            $output = $serializer->item($item->getResourceKey(), $itemData);
        } elseif (\is_callable($serializer)) {
            // Serialize via a callable/closure
            $output = $serializer($item->getResourceKey(), $itemData);
        } else {
            // No serialization
            $output = $itemData;
        }

        return $output;
    }

    /**
     * Get the current includes object
     * @return Includes
     */
    public function getIncludes(): Includes
    {
        return $this->includes ?? new Includes();
    }

    /**
     * Set the Includes object used for determining included resources
     * @param Includes $includes
     * @return $this
     */
    public function setIncludes(Includes $includes)
    {
        $this->includes = $includes;

        return $this;
    }

    /**
     * Parse the given string to generate a new Includes object
     * @param string $str
     * @return $this
     */
    public function parseIncludes($str)
    {
        $this->includes = $this->getIncludeParser()->parse(!empty($str) ? $str : '');

        return $this;
    }

    /**
     * Return the include parser object.
     * If not set explicitly via setIncludeParser(), it will return the default IncludeParser object
     * @return IncludeParserInterface
     * @see Smokescreen::setIncludeParser()
     */
    public function getIncludeParser(): IncludeParserInterface
    {
        return $this->includeParser ?? new IncludeParser();
    }

    /**
     * Set the include parser to handle converting a string to an Includes object
     * @param IncludeParserInterface $includeParser
     * @return $this
     */
    public function setIncludeParser(IncludeParserInterface $includeParser)
    {
        $this->includeParser = $includeParser;

        return $this;
    }

    /**
     * Get the current relation loader
     * @return RelationLoaderInterface|null
     */
    public function getRelationLoader()
    {
        return $this->relationLoader;
    }

    /**
     * Set the relationship loader
     * @param RelationLoaderInterface $relationLoader
     * @return $this
     */
    public function setRelationLoader(RelationLoaderInterface $relationLoader)
    {
        $this->relationLoader = $relationLoader;

        return $this;
    }

    /**
     * Returns true if a RelationLoaderInterface object has been defined
     * @return bool
     */
    public function hasRelationLoader(): bool
    {
        return $this->relationLoader !== null;
    }
}


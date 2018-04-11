<?php

namespace Rexlabs\Smokescreen;

use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Exception\MissingResourceException;
use Rexlabs\Smokescreen\Exception\UnhandledResourceType;
use Rexlabs\Smokescreen\Helpers\JsonHelper;
use Rexlabs\Smokescreen\Includes\IncludeParser;
use Rexlabs\Smokescreen\Includes\IncludeParserInterface;
use Rexlabs\Smokescreen\Includes\Includes;
use Rexlabs\Smokescreen\Relations\RelationLoaderInterface;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;
use Rexlabs\Smokescreen\Serializer\DefaultSerializer;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;

/**
 * Smokescreen is a library for transforming and serializing data - typically RESTful API output.
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
     * Return the current resource.
     *
     * @return ResourceInterface|mixed|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set the resource to be transformed.
     *
     * @param ResourceInterface|mixed|null $resource
     *
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set the resource item to be transformed.
     *
     * @param mixed                           $data
     * @param TransformerInterface|mixed|null $transformer
     * @param string|null                     $key
     *
     * @return $this
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     */
    public function item($data, $transformer = null, $key = null)
    {
        $this->setResource(new Item($data, $transformer, $key));

        return $this;
    }

    /**
     * Set the resource collection to be transformed.
     *
     * @param mixed                           $data
     * @param TransformerInterface|mixed|null $transformer
     * @param string|null                     $key
     * @param callable|null                   $callback
     *
     * @return $this
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     */
    public function collection($data, TransformerInterface $transformer = null, $key = null, callable $callback = null)
    {
        $this->setResource(new Collection($data, $transformer, $key));
        if ($callback !== null) {
            $callback($this->resource);
        }

        return $this;
    }

    /**
     * Sets the transformer to be used to transform the resource ... later.
     *
     * @throws MissingResourceException
     *
     * @return TransformerInterface|mixed|null
     */
    public function getTransformer()
    {
        if (!$this->resource) {
            throw new MissingResourceException('Resource must be specified before setting a transformer');
        }

        return $this->resource->getTransformer();
    }

    /**
     * Sets the transformer to be used to transform the resource ... later.
     *
     * @param TransformerInterface|mixed|null $transformer
     *
     * @throws MissingResourceException
     *
     * @return $this
     */
    public function setTransformer($transformer = null)
    {
        if (!$this->resource) {
            throw new MissingResourceException('Resource must be specified before setting a transformer');
        }
        $this->resource->setTransformer($transformer);

        return $this;
    }

    /**
     * Returns an object (stdClass) representation of the transformed/serialized data.
     *
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     *
     * @return \stdClass
     * @throws IncludeException
     */
    public function toObject(): \stdClass
    {
        return (object) json_decode($this->toJson(), false);
    }

    /**
     * Outputs a JSON string of the resulting transformed and serialized data.
     *
     * @param int $options
     *
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     *
     * @return string
     * @throws IncludeException
     */
    public function toJson($options = 0): string
    {
        return JsonHelper::encode($this->jsonSerialize(), $options);
    }

    /**
     * Output the transformed and serialized data as an array.
     * Implements PHP's JsonSerializable interface.
     *
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     *
     * @return array
     *
     * @see Smokescreen::toArray()
     * @throws IncludeException
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Return the transformed data as an array.
     *
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     *
     * @return array
     * @throws IncludeException
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
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer ?? new DefaultSerializer();
    }

    /**
     * Set the serializer which will be used to output the transformed resource.
     *
     * @param SerializerInterface|null $serializer
     *
     * @return $this
     */
    public function setSerializer(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Get the current includes object.
     *
     * @return Includes
     */
    public function getIncludes(): Includes
    {
        return $this->includes ?? new Includes();
    }

    /**
     * Set the Includes object used for determining included resources.
     *
     * @param Includes $includes
     *
     * @return $this
     */
    public function setIncludes(Includes $includes)
    {
        $this->includes = $includes;

        return $this;
    }

    /**
     * Parse the given string to generate a new Includes object.
     *
     * @param string $str
     *
     * @return $this
     */
    public function parseIncludes($str)
    {
        $this->includes = $this->getIncludeParser()->parse(!empty($str) ? $str : '');

        return $this;
    }

    /**
     * Return the include parser object.
     * If not set explicitly via setIncludeParser(), it will return the default IncludeParser object.
     *
     * @return IncludeParserInterface
     *
     * @see Smokescreen::setIncludeParser()
     */
    public function getIncludeParser(): IncludeParserInterface
    {
        return $this->includeParser ?? new IncludeParser();
    }

    /**
     * Set the include parser to handle converting a string to an Includes object.
     *
     * @param IncludeParserInterface $includeParser
     *
     * @return $this
     */
    public function setIncludeParser(IncludeParserInterface $includeParser)
    {
        $this->includeParser = $includeParser;

        return $this;
    }

    /**
     * Get the current relation loader.
     *
     * @return RelationLoaderInterface|null
     */
    public function getRelationLoader()
    {
        return $this->relationLoader;
    }

    /**
     * Set the relationship loader.
     *
     * @param RelationLoaderInterface $relationLoader
     *
     * @return $this
     */
    public function setRelationLoader(RelationLoaderInterface $relationLoader)
    {
        $this->relationLoader = $relationLoader;

        return $this;
    }

    /**
     * Returns true if a RelationLoaderInterface object has been defined.
     *
     * @return bool
     */
    public function hasRelationLoader(): bool
    {
        return $this->relationLoader !== null;
    }

    /**
     * @param ResourceInterface|mixed $resource
     * @param Includes                $includes
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     *
     * @return array|mixed
     * @throws IncludeException
     */
    protected function serializeResource($resource, Includes $includes): array
    {
        // Load relations for any resource which implements the interface.
        if ($resource instanceof ResourceInterface) {
            $this->loadRelations($resource);
        }

        // Build the output by recursively transforming each resource.
        $output = null;
        if ($resource instanceof Collection) {
            $output = $this->serializeCollection($resource, $includes);
        } elseif ($resource instanceof Item) {
            $output = $this->serializeItem($resource, $includes);
        } elseif (\is_array($resource)) {
            $output = $resource;
        } elseif (\is_object($resource) && method_exists($resource, 'toArray')) {
            $output = $resource->toArray();
        } else {
            throw new UnhandledResourceType('Unable to serialize resource of type '.\gettype($resource));
        }

        return $output;
    }

    /**
     * Fire the relation loader (if defined) for this resource.
     *
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
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws InvalidTransformerException
     *
     * @return array
     * @throws IncludeException
     */
    protected function serializeCollection(Collection $collection, Includes $includes): array
    {
        // Get the globally set serializer (resource may override).
        $defaultSerializer = $this->getSerializer();

        // Collection resources implement IteratorAggregate ... so that's nice.
        $items = [];
        foreach ($collection as $item) {
            // $item might be a Model or an array etc.
            $items[] = $this->transformItem($item, $collection->getTransformer(), $includes);
        }

        // The collection can have a custom serializer defined.
        $serializer = $collection->getSerializer() ?? $defaultSerializer;

        if ($serializer instanceof SerializerInterface) {
            // Serialize via object implementing SerializerInterface
            $output = $serializer->collection($collection->getResourceKey(), $items);
            if ($collection->hasPaginator()) {
                $output = array_merge($output, $serializer->paginator($collection->getPaginator()));
            }
        } elseif (\is_callable($serializer)) {
            // Serialize via a callable/closure
            $output = $serializer($collection->getResourceKey(), $items);
        } else {
            // Serialization disabled for this resource
            $output = $items;
        }

        return $output;
    }

    /**
     * Apply transformation to the item.
     *
     * @param mixed                                    $item
     * @param mixed|TransformerInterface|callable|null $transformer
     * @param Includes                                 $includes
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     *
     * @return array
     * @throws IncludeException
     */
    protected function transformItem($item, $transformer, Includes $includes): array
    {
        if ($transformer === null) {
            // No transformation can be applied.
            return (array) $item;
        }
        if (\is_callable($transformer)) {
            // Callable should simply return an array.
            return (array) $transformer($item);
        }

        // Only these keys may be mapped
        $availableIncludeKeys = $transformer->getAvailableIncludes();

        // Wanted includes is a either the explicit includes requested, or the defaults for the transformer.
        $wantIncludeKeys = $includes->baseKeys() ?: $transformer->getDefaultIncludes();

        // Find the keys that are declared in the $includes of the transformer
        $mappedIncludeKeys = array_filter($wantIncludeKeys, function ($includeKey) use ($availableIncludeKeys) {
            return \in_array($includeKey, $availableIncludeKeys, true);
        });

        // We can consider our props anything that has not been mapped.
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
        $data = (array) $transformer->transform($item);

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
            $resource = $this->executeTransformerInclude($transformer, $includeKey, $includeMap[$includeKey], $item);

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

    /**
     * Execute the transformer.
     *
     * @param mixed  $transformer
     * @param string $includeKey
     * @param array  $includeDefinition
     * @param mixed  $item
     *
     * @return ResourceInterface
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws IncludeException
     */
    protected function executeTransformerInclude($transformer, $includeKey, $includeDefinition, $item)
    {
        // Transformer explicitly provided an include method
        $method = $includeDefinition['method'];
        if (method_exists($transformer, $method)) {
            return $transformer->$method($item);
        }

        // Otherwise try handle the include automatically
        return $this->autoWireInclude($includeKey, $includeDefinition, $item);
    }

    /**
     * @param string $includeKey
     * @param array  $includeDefinition
     * @param        $item
     *
     * @return Collection|Item
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws IncludeException
     */
    protected function autoWireInclude($includeKey, $includeDefinition, $item)
    {
        // Get the included data
        $data = null;
        if (\is_array($item) || $item instanceof \ArrayAccess) {
            $data = $item[$includeKey];
        } elseif (\is_object($item) && property_exists($item, $includeKey)) {
            $data = $item->$includeKey;
        } else {
            throw new IncludeException("Cannot auto-wire include for $includeKey: Cannot get include data");
        }

        // Wrap the included data in a resource
        $resourceType = $includeDefinition['resource_type'] ?? 'item';
        switch ($resourceType) {
            case 'collection':
                return new Collection($data);
            case 'item':
                return new Item($data);
            default:
                throw new IncludeException("Cannot auto-wire include for $includeKey: Invalid resource type $resourceType");
        }
    }

    /**
     * Applies the serializer to the Item resource.
     *
     * @param Item     $item
     * @param Includes $includes
     *
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws InvalidTransformerException
     *
     * @return array
     */
    protected function serializeItem(Item $item, Includes $includes): array
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
}

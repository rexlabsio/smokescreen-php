<?php

namespace Rexlabs\Smokescreen;

use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Exception\MissingResourceException;
use Rexlabs\Smokescreen\Exception\UnhandledResourceType;
use Rexlabs\Smokescreen\Helpers\ArrayHelper;
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
use Rexlabs\Smokescreen\Transformer\Scope;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerResolverInterface;

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

    /** @var TransformerResolverInterface */
    protected $transformerResolver;

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
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     *
     * @return $this
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
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     *
     * @return $this
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
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     *
     * @return \stdClass
     */
    public function toObject(): \stdClass
    {
        return (object) json_decode($this->toJson());
    }

    /**
     * Outputs a JSON string of the resulting transformed and serialized data.
     *
     * @param int $options
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     *
     * @return string
     */
    public function toJson($options = 0): string
    {
        return JsonHelper::encode($this->jsonSerialize(), $options);
    }

    /**
     * Output the transformed and serialized data as an array.
     * Implements PHP's JsonSerializable interface.
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     *
     * @return array
     *
     * @see Smokescreen::toArray()
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Return the transformed data as an array.
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceType
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource) {
            throw new MissingResourceException('No resource has been defined to transform');
        }

        // Kick of serialization of the resource
        $scope = new Scope($this->resource, $this->getIncludes());
        return $this->transformResource($scope);
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
     *
     * @param Scope $scope
     *
     * @return array|mixed
     * @throws IncludeException
     */
    protected function transformResource(Scope $scope): array
    {
        $resource = $scope->resource();
        if (!($resource instanceof ResourceInterface)) {
            if (\is_array($resource)) {
                return $resource;
            }
            if (\is_object($resource) && method_exists($resource, 'toArray')) {
                return $resource->toArray();
            }
            throw new UnhandledResourceType('Unable to serialize resource of type '.\gettype($resource));
        }

        // Try to resolve a transformer for a resource that does not have one assigned.
        if (!$resource->hasTransformer()) {
            $transformer = $this->resolveTransformerForResource($resource);
            $resource->setTransformer($transformer);
        }

        // Call the relationship loader for any relations
        $this->loadRelations($resource, $scope->resolvedRelationshipKeys());

        // Build the output by recursively transforming each resource.
        $output = null;
        if ($resource instanceof Collection) {
            $output = $this->transformCollectionResource($scope);
        } elseif ($resource instanceof Item) {
            $output = $this->transformItemResource($scope);
        }

        return $output;
    }

    /**
     * Fire the relation loader (if defined) for this resource.
     *
     * @param ResourceInterface $resource
     * @param array             $relationshipKeys
     */
    protected function loadRelations(ResourceInterface $resource, array $relationshipKeys)
    {
        if ($this->relationLoader !== null && !empty($relationshipKeys)) {
            print_r($relationshipKeys);
            $this->relationLoader->load($resource, $relationshipKeys);
        }
    }

    /**
     * @param Scope $scope
     *
     * @return array
     * @throws IncludeException
     */
    protected function transformCollectionResource(Scope $scope): array
    {
        // Get the globally set serializer (resource may override).
        $defaultSerializer = $this->getSerializer();

        // Collection resources implement IteratorAggregate ... so that's nice.
        // TODO: Check type?
        $collection = $scope->resource();

        $items = [];
        foreach ($collection as $itemData) {
            // $item might be a Model or an array etc.
            $items[] = $this->transformData($scope, $itemData);
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
     * @param Scope $scope
     * @param mixed $data
     *
     * @return array
     * @throws IncludeException
     */
    protected function transformData(Scope $scope, $data): array
    {
        // Get the base data from the transformation
        $transformedData = $scope->transform($data);

        // Add includes to the payload
        $includeMap = $scope->includeMap();
        foreach ($scope->resolvedIncludeKeys() as $includeKey) {
            $resource = $this->executeTransformerInclude($scope, $includeKey, $includeMap[$includeKey], $data);

            // Create a new scope
            $newScope = new Scope($resource, $scope->includes()->splice($includeKey), $scope);

            if ($resource instanceof ResourceInterface) {
                // Resource object
                ArrayHelper::mutate(
                    $transformedData,
                    $resource->getResourceKey() ?: $includeKey,
                    $resource->getData() ? $this->transformResource($newScope) : null
                );
            } else {
                // Plain old array
                ArrayHelper::mutate(
                    $transformedData,
                    $includeKey,
                    $this->transformResource($newScope)
                );
            }
        }

        return $transformedData;
    }

    /**
     * Execute the transformer.
     *
     * @param Scope  $scope
     * @param string $includeKey
     * @param array  $includeDefinition
     * @param mixed  $data
     *
     * @return ResourceInterface
     * @throws IncludeException
     */
    protected function executeTransformerInclude(Scope $scope, $includeKey, $includeDefinition, $data)
    {
        // Transformer explicitly provided an include method
        $transformer = $scope->transformer();
        $method = $includeDefinition['method'];
        if (method_exists($transformer, $method)) {
            return $transformer->$method($data, $scope);
        }

        // Otherwise try handle the include automatically
        return $this->autoWireInclude($includeKey, $includeDefinition, $data);
    }

    /**
     * @param string $includeKey
     * @param array  $includeDefinition
     * @param        $item
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     *
     * @return Collection|Item
     */
    protected function autoWireInclude($includeKey, $includeDefinition, $item)
    {
        // Get the included data
        $data = null;
        if (\is_array($item) || $item instanceof \ArrayAccess) {
            $data = $item[$includeKey] ?? null;
        } elseif (\is_object($item)) {
            $data = $item->$includeKey ?? null;
        } else {
            throw new IncludeException("Cannot auto-wire include for $includeKey: Cannot get include data");
        }

        if (!empty($includeDefinition['resource_type']) && $includeDefinition['resource_type'] === 'collection') {
            return new Collection($data);
        }

        // Assume unless declared, that the resource is an item.
        return new Item($data);
    }

    /**
     * Applies the serializer to the Item resource.
     *
     * @param Scope $scope
     *
     * @return array
     * @throws IncludeException
     */
    protected function transformItemResource(Scope $scope): array
    {
        // Get the globally set serializer (resource may override)
        $defaultSerializer = $this->getSerializer();


        // The collection can have a custom serializer defined
        // TODO: Check resource type is item
        $item = $scope->resource();
        $serializer = $item->getSerializer() ?? $defaultSerializer;
        $isSerializerInterface = $serializer instanceof SerializerInterface;

        // Transform the item data
        $itemData = $this->transformData($scope, $item->getData());

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
     * Resolve the transformer to be used for a resource.
     * Returns an interface, callable or null when a transformer cannot be resolved.
     *
     * @param $resource
     *
     * @return TransformerInterface|mixed|null
     */
    protected function resolveTransformerForResource($resource)
    {
        $transformer = null;

        if ($this->transformerResolver !== null) {
            $transformer = $this->transformerResolver->resolve($resource);
        }

        return $transformer;
    }

    /**
     * @return TransformerResolverInterface|null
     */
    public function getTransformerResolver()
    {
        return $this->transformerResolver;
    }

    /**
     * Set the transformer resolve to user.
     *
     * @param TransformerResolverInterface|null $transformerResolver
     *
     * @return $this
     */
    public function setTransformerResolver(TransformerResolverInterface $transformerResolver = null)
    {
        $this->transformerResolver = $transformerResolver;

        return $this;
    }
}

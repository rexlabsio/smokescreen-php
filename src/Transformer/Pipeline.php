<?php

namespace Rexlabs\Smokescreen\Transformer;

use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Exception\UnhandledResourceType;
use Rexlabs\Smokescreen\Helpers\ArrayHelper;
use Rexlabs\Smokescreen\Relations\RelationLoaderInterface;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;

class Pipeline
{
    /** @var SerializerInterface */
    protected $serializer;

    /** @var TransformerResolverInterface|null */
    protected $transformerResolver;

    /** @var RelationLoaderInterface|null */
    protected $relationLoader;

    public function __construct()
    {
    }

    public function setTransformerResolver(TransformerResolverInterface $transformerResolver)
    {
        $this->transformerResolver = $transformerResolver;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function setRelationLoader(RelationLoaderInterface $relationLoader)
    {
        $this->relationLoader = $relationLoader;
    }

    /**
     * @param Scope $scope
     *
     * @throws IncludeException
     *
     * @return array
     */
    public function transform(Scope $scope): array
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
     * Applies the serializer to the Item resource.
     *
     * @param Scope $scope
     *
     * @throws IncludeException
     *
     * @return array
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
     * @param Scope $scope
     *
     * @throws IncludeException
     *
     * @return array
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
     * Apply transformation to the item data.
     *
     * @param Scope $scope
     * @param mixed $data
     *
     * @throws IncludeException
     *
     * @return array
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
                    $resource->getData() ? $this->transform($newScope) : null
                );
            } else {
                // Plain old array
                ArrayHelper::mutate(
                    $transformedData,
                    $includeKey,
                    $this->transform($newScope)
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
     * @throws IncludeException
     *
     * @return ResourceInterface
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
     * Fire the relation loader (if defined) for this resource.
     *
     * @param ResourceInterface $resource
     * @param array             $relationshipKeys
     */
    protected function loadRelations(ResourceInterface $resource, array $relationshipKeys)
    {
        if ($this->relationLoader !== null && !empty($relationshipKeys)) {
            $this->relationLoader->load($resource, $relationshipKeys);
        }
    }

    /**
     * @param string $includeKey
     * @param array  $includeDefinition
     * @param        $item
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     *
     * @return Collection|Item|ResourceInterface
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

    protected function getSerializer()
    {
        return $this->serializer;
    }
}

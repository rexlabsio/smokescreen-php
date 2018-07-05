<?php

namespace Rexlabs\Smokescreen\Transformer;

use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
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
     * @return array|null
     */
    public function transform(Scope $scope)
    {
        $resource = $scope->resource();
        
        // When we encounter a null resource, we just return null because
        // there's nothing else we can do with it.
        if ($resource === null) {
            return null;
        }
        
        // If the resource is not a ResourceInterface instance we allow it to be
        // something array-like.
        if (!($resource instanceof ResourceInterface)) {
            if (\is_array($resource)) {
                return $resource;
            }
            if (\is_object($resource) && method_exists($resource, 'toArray')) {
                return $resource->toArray();
            }

            throw new UnhandledResourceType('Unable to serialize resource of type ' . \gettype($resource));
        }

        // Try to resolve a transformer for a resource that does not have one
        // assigned.
        if (!$resource->hasTransformer()) {
            $transformer = $this->resolveTransformerForResource($resource);
            $resource->setTransformer($transformer);
        }

        // Call the relationship loader for any relations
        $this->loadRelations($resource, $scope->resolvedRelationshipKeys());

        // We can only transform the resource data if we have some data to
        // transform ...
        if (($data = $resource->getData()) !== null) {
            // Build the data by recursively transforming each resource.
            if ($resource instanceof Collection) {
                $data = [];
                foreach ($resource as $itemData) {
                    $data[] = $this->transformData($scope, $itemData);
                }
            } elseif ($resource instanceof Item) {
                $data = $this->transformData($scope, $data);
            }
        }

        // Serialize the transformed data
        return $this->serialize($resource, $data);
    }

    /**
     * Serialize the data
     * @param ResourceInterface $resource
     * @param                   $data
     *
     * @return array|mixed
     */
    protected function serialize(ResourceInterface $resource, $data)
    {
        // Get the serializer from the resource, or use the default.
        $serializer = $resource->getSerializer() ?? $this->getSerializer();
        
        if ($serializer instanceof SerializerInterface) {
            if ($resource instanceof Collection) {
                if ($data === null) {
                    return $serializer->nullCollection();
                }
                $data = $serializer->collection($resource->getResourceKey(), $data);
                if ($resource->hasPaginator()) {
                    $data = array_merge($data, $serializer->paginator($resource->getPaginator()));
                }
            } elseif ($resource instanceof Item) {
                if ($data === null) {
                    return $serializer->nullItem();
                }
                $data = $serializer->item($resource->getResourceKey(), $data);
            }
        } elseif (\is_callable($serializer)) {
            // Serialize via a callable/closure
            $data = $serializer($resource->getResourceKey(), $data);
        }
       
        return $data;
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
        $transformer = $scope->transformer();

        // Handle when no transformer is present
        if (empty($transformer)) {
            // No transformation
            return (array) $data;
        }

        // Handle when transformer is a callable
        if (\is_callable($transformer)) {
            // Simply run callable on the data and return the result
            return (array) $transformer($data);
        }

        // Ensure we're working with a real transformer from this point forward.
        if (!($transformer instanceof TransformerInterface)) {
            throw new InvalidTransformerException('Expected a valid transformer');
        }

        // Transform the data, and filter any sparse field-set for the scope.
        $transformedData = $scope->filterData($transformer->getTransformedData($data));

        // Add includes to the payload.
        $includeMap = $scope->includeMap();
        foreach ($scope->resolvedIncludeKeys() as $includeKey) {
            $child = $this->executeTransformerInclude($scope, $includeKey, $includeMap[$includeKey], $data);

            // Create a new child scope.
            $childScope = new Scope($child, $scope->includes()->splice($includeKey), $scope);

            // If working with a ResourceInterface, use it's own key (if present).
            $childKey = $child instanceof ResourceInterface && $child->getResourceKey() ?
                $child->getResourceKey() : $includeKey;

            ArrayHelper::mutate(
                $transformedData,
                $childKey,
                $this->transform($childScope)
            );
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

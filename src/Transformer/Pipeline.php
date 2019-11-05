<?php

namespace Rexlabs\Smokescreen\Transformer;

use ArrayAccess;
use Rexlabs\Smokescreen\Compositor\CompositorInterface;
use Rexlabs\Smokescreen\Compositor\DefaultCompositor;
use Rexlabs\Smokescreen\Exception\IncludeException;
use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Exception\UnhandledResourceTypeException;
use Rexlabs\Smokescreen\Relations\RelationLoaderInterface;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use function is_array;
use function is_callable;
use function is_object;

class Pipeline
{
    /** @var SerializerInterface|null */
    protected $serializer;

    /** @var CompositorInterface|null */
    protected $compositor;

    /** @var RelationLoaderInterface|null */
    protected $relationLoader;

    /** @var TransformerResolverInterface|null */
    protected $transformerResolver;

    /**
     * @param TransformerResolverInterface $transformerResolver
     *
     * @return void
     */
    public function setTransformerResolver(TransformerResolverInterface $transformerResolver)
    {
        $this->transformerResolver = $transformerResolver;
    }

    /**
     * @param SerializerInterface $serializer
     *
     * @return void
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param CompositorInterface $compositor
     *
     * @return void
     */
    public function setCompositor(CompositorInterface $compositor)
    {
        $this->compositor = $compositor;
    }

    /**
     * @param RelationLoaderInterface $relationLoader
     *
     * @return void
     */
    public function setRelationLoader(RelationLoaderInterface $relationLoader)
    {
        $this->relationLoader = $relationLoader;
    }

    /**
     * Run the transformation pipeline:
     *   create includes tree
     *   transform nodes
     *   serialize and compose scopes.
     *
     * @param Scope $root
     *
     * @throws IncludeException
     *
     * @return array|null
     */
    public function run(Scope $root)
    {
        $this->createIncludesTree($root);

        // Transform all nodes
        foreach ($root->nodeTraversal() as $node) {
            $this->transformNode($node);
        }

        /*
         * Serialise and compose all scopes
         *
         * Serializing often nests the transformed data in a "data" node
         * This can make it difficult to compose child nodes onto a parent
         * Issue is avoided by traversing depth first (post-order)
         *  1. leaf scope is searialized
         *  2. leaf scope composed with unserialized parent node / root node
         *  3. parent scope serialized
         */
        $rootData = [];
        foreach ($root->scopeTraversal() as $scope) {
            // Serialize first
            $this->serializeScope($scope);

            // Compose, optionally attaching to transformed (but not yet serialized) node
            $this->composeScope($rootData, $scope);
        }

        return $this->mergeRoot($root->getSerializedData(), $rootData);
    }

    /**
     * @param array|null $rootData
     * @param array      $includesData
     *
     * @return array|null
     */
    protected function mergeRoot($rootData, array $includesData)
    {
        if (!is_array($rootData)) {
            return $rootData;
        }

        return array_merge($rootData, $includesData);
    }

    /**
     * @param Node $node
     *
     * @return void
     */
    protected function transformNode(Node $node)
    {
        $transformedData = $this->transform(
            $node->getContainingScope(),
            $node->getData()
        );
        $node->setTransformedData($transformedData);
    }

    /**
     * @param Scope $scope
     *
     * @return void
     */
    protected function serializeScope(Scope $scope)
    {
        $serializedData = $this->serialize(
            $scope->resource(),
            $scope->getTransformedData(),
            $scope->includeKey()
        );
        $scope->setSerializedData($serializedData);
    }

    /**
     * @param array $rootData
     * @param Scope $scope
     *
     * @return void
     */
    protected function composeScope(array &$rootData, Scope $scope)
    {
        $parent = $scope->parent();
        $key = $scope->includeKey();

        // Skip the root node
        if ($parent === null || $key === null) {
            return;
        }

        $compositor = $scope->getResourceCompositor()
            ?? $this->compositor
            ?? new DefaultCompositor();

        // Take current scope's serialized data and
        // apply it to parent node's transformed data
        $parentData = $parent->getTransformedData() ?? [];
        $data = $scope->getSerializedData();

        if ($compositor instanceof CompositorInterface) {
            if ($scope->isCollection()) {
                $compositor->composeIncludedCollection($rootData, $parentData, $key, $data);
            } else {
                $compositor->composeIncludedItem($rootData, $parentData, $key, $data);
            }
        } elseif (is_callable($compositor)) {
            $compositor($rootData, $parentData, $key, $data);
        }

        $parent->setTransformedData($parentData);
    }

    /**
     * @param Scope $scope
     *
     * @throws IncludeException
     *
     * @return void
     */
    protected function createIncludesTree(Scope $scope)
    {
        $this->prepareScopeResource($scope);

        // Create one (item) or many (collection) nodes
        $scope->setNodes($this->createNodes($scope));

        // Add included scopes to each node
        $includeMap = $scope->includeMap();
        foreach ((array) $scope->getNodes() as $node) {
            $node->setIncludedScopes(
                array_map(function (string $includeKey) use ($node, $includeMap) {
                    return $this->createIncludedScope($node, $includeMap, $includeKey);
                }, $scope->resolvedIncludeKeys())
            );
        }

        // Recursively create nested nodes and scopes
        foreach ($scope->getIncludedScopes() as $includedScope) {
            $this->createIncludesTree($includedScope);
        }
    }

    /**
     * @param Scope $scope
     *
     * @throws UnhandledResourceTypeException
     *
     * @return null|Node[]
     */
    protected function createNodes(Scope $scope)
    {
        $resource = $scope->resource();

        // Get raw data for nodes in this scope
        if ($resource instanceof ResourceInterface) {
            $dataItems = $resource->getData();
        } elseif (is_object($resource) && method_exists($resource, 'toArray')) {
            $dataItems = $resource->toArray();
        } elseif (is_array($resource) || $resource === null) {
            $dataItems = $resource;
        } else {
            throw new UnhandledResourceTypeException('Unable to serialize resource of type '.gettype($resource));
        }

        // Only collections have multiple nodes per scope
        if (!$resource instanceof Collection) {
            $dataItems = [$dataItems];
        }

        // Null collection stay null
        if ($dataItems === null) {
            return null;
        }

        // Use foreach instead of array_map to support traversable collections
        $nodes = [];
        foreach ($dataItems as $dataItem) {
            $nodes[] = new Node($scope, $dataItem);
        }

        return $nodes;
    }

    /**
     * @param Node   $node
     * @param array  $includeMap
     * @param string $includeKey
     *
     * @throws IncludeException
     *
     * @return Scope
     */
    protected function createIncludedScope(
        Node $node,
        array $includeMap,
        string $includeKey
    ): Scope {
        $childResource = $this->executeTransformerInclude(
            $node->getContainingScope(),
            $includeKey,
            $includeMap[$includeKey],
            $node->getData()
        );
        $includes = $node->getContainingScope()->includes()->splice($includeKey);

        // If working with a ResourceInterface, use it's own key (if present).
        $childKey = $childResource instanceof ResourceInterface && $childResource->getResourceKey()
            ? $childResource->getResourceKey()
            : $includeKey;

        return new Scope($childResource, $includes, $node, $childKey);
    }

    /**
     * @param Scope $scope
     *
     * @return void
     */
    protected function prepareScopeResource(Scope $scope)
    {
        $resource = $scope->resource();
        if (!$resource instanceof ResourceInterface) {
            return;
        }

        // Try to resolve a transformer for a resource that does not have one
        // assigned.
        if (!$resource->hasTransformer()) {
            $transformer = $this->resolveTransformerForResource($resource);
            $resource->setTransformer($transformer);
        }

        // Call the relationship loader for any relations
        $this->loadRelations($resource, $scope->resolvedRelationshipKeys());
    }

    /**
     * Serialize the data.
     *
     * @param ResourceInterface|mixed|null $resource
     * @param array|null                   $data
     * @param string|null                  $includeKey
     *
     * @return array|mixed
     */
    protected function serialize($resource, $data, $includeKey = null)
    {
        // Get the serializer from the resource, or use the default.
        $serializer = $resource instanceof ResourceInterface
            ? $resource->getSerializer()
            : null;
        if ($serializer === null) {
            $serializer = $this->getSerializer();
        }

        if ($serializer instanceof SerializerInterface) {
            if ($resource instanceof Collection) {
                return $this->serializeCollection($serializer, $resource, $data, $includeKey);
            }

            if ($resource instanceof Item) {
                return $this->serializeItem($serializer, $data, $includeKey);
            }
        }
        if (is_callable($serializer)) {
            // Serialize via a callable/closure
            return $serializer($includeKey, $data);
        }

        return $data;
    }

    /**
     * @param SerializerInterface $serializer
     * @param Collection          $resource
     * @param                     $data
     * @param string|null         $includeKey
     *
     * @return mixed
     */
    protected function serializeCollection(
        SerializerInterface $serializer,
        Collection $resource,
        $data,
        $includeKey
    ) {
        if ($data === null) {
            return $serializer->nullCollection();
        }
        $data = $serializer->collection($includeKey, $data);
        if ($resource->hasPaginator()) {
            $data = array_merge($data, $serializer->paginator($resource->getPaginator()));
        }

        return $data;
    }

    /**
     * @param SerializerInterface $serializer
     * @param array|null          $data
     * @param string|null         $includeKey
     *
     * @return array|mixed
     */
    protected function serializeItem(
        SerializerInterface $serializer,
        $data,
        $includeKey = null
    ) {
        return $data === null
            ? $serializer->nullItem()
            : $serializer->item($includeKey, $data);
    }

    /**
     * Apply transformation to the item data.
     *
     * @param Scope $scope
     * @param mixed $data
     *
     * @return array|null
     */
    protected function transform(Scope $scope, $data)
    {
        $transformer = $scope->transformer();

        if ($data === null) {
            return null;
        }

        // Handle when no transformer is present
        if (empty($transformer)) {
            // No transformation
            return (array) $data;
        }

        // Handle when transformer is a callable
        if (is_callable($transformer)) {
            // Simply run callable on the data and return the result
            return (array) $transformer($data);
        }

        // Ensure we're working with a real transformer from this point forward.
        if (!($transformer instanceof TransformerInterface)) {
            throw new InvalidTransformerException('Expected a valid transformer');
        }

        // Transform the data, and filter any sparse field-set for the scope.
        return $scope->filterData($transformer->getTransformedData($data));
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
     * @return ResourceInterface|mixed
     */
    protected function executeTransformerInclude(
        Scope $scope,
        $includeKey,
        $includeDefinition,
        $data
    ) {
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
     * @throws IncludeException
     *
     * @return Collection|Item|ResourceInterface
     */
    protected function autoWireInclude($includeKey, $includeDefinition, $item)
    {
        // Get the included data
        $data = null;
        if (is_array($item) || $item instanceof ArrayAccess) {
            $data = $item[$includeKey] ?? null;
        } elseif (is_object($item)) {
            $data = $item->$includeKey ?? null;
        } else {
            throw new IncludeException("Cannot auto-wire include for {$includeKey}: Cannot get include data");
        }

        if (!empty($includeDefinition['resource_type']) && $includeDefinition['resource_type'] === 'collection') {
            return new Collection($data);
        }

        // Assume unless declared, that the resource is an item.
        return new Item($data);
    }

    /**
     * @return SerializerInterface|mixed
     */
    protected function getSerializer()
    {
        return $this->serializer;
    }
}

<?php


namespace RexSoftware\Smokescreen\Scope;


use RexSoftware\Smokescreen\Helpers\StrHelper;
use RexSoftware\Smokescreen\Relations\RelationLoaderInterface;
use RexSoftware\Smokescreen\Resource\Item;
use RexSoftware\Smokescreen\Resource\ResourceInterface;
use RexSoftware\Smokescreen\Serializer\SerializerInterface;
use RexSoftware\Smokescreen\Transformer\TransformerInterface;

class Compiler
{
    /** @var Scope */
    protected $scope;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var RelationLoaderInterface|null */
    protected $loader;

    public function __construct(Scope $scope, SerializerInterface $serializer, RelationLoaderInterface $loader = null)
    {
        $this->scope = $scope;
        $this->serializer = $serializer;
        $this->loader = $loader;
    }

    public function compile()
    {
        $output = [];


    }

    /**
     * @param Scope $scope
     * @return array
     * @throws \RexSoftware\Smokescreen\Exception\MissingTransformerException
     */
    protected function serialize(Scope $scope): array
    {
        // Relationship loading
        $this->loadRelations($scope->getResource());

        // Build the output by recursively transforming each resource
        return $scope->isCollection() ?
            $this->serializeCollection($scope) :
            $this->serializeItem($scope);
    }

    protected function loadRelations(ResourceInterface $resource)
    {
        if ($this->loader !== null) {
            $this->loader->load($resource);
        }
    }

    protected function serializeCollection(Scope $scope)
    {
        $items = array_map(function ($data) use ($scope) {

            $scope = new Scope(
                new Item(
                    $data,
                    $scope->getResource()->getTransformer(),
                    $scope->getResource()->getResourceKey()
                ),
                $scope->getIncludes()
            );

            return $this->transformItem($item);
        }, $scope->getResource()->toArray());

        // Serialize all the items
        $output = $this->serializer->collection(
            $collection->getResourceKey(),
            $items
        );

        // Merge in any pagination
        if ($collection->hasPaginator()) {
            $output = array_merge($output, $this->serializer->paginator($collection->getPaginator()));
        }

        return $output;
    }

    protected function transformItem(Item $item): array
    {
        $transformer = $item->getTransformer();
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
            throw new \InvalidArgumentException('Transformer must implement TransformerInterface');
        }

        // We need to double-check it has a transform() method - perhaps this could be done on set?
        // Since PHP doesn't support contravariance, we can't define the transform() signature on the interface
        if (!method_exists($transformer, 'transform')) {
            throw new \InvalidArgumentException('Transformer must provide a transform() method');
        }

        // Get the base data from the transformation
        // TODO: Oh waits ... we need to get the sparse fieldset first which we determine from includes
        $data = (array)$transformer->transform($item);

        // Merge the includes
        // To determine what includes we want, we need to merge the
        // requested includes (GET) and the transformer's default includes.
        // Then we compare that to the available includes ...
        $defaultIncludes = $transformer->getDefaultIncludes();
//        $availableIncludes = $transformer->getAvailableIncludes();
//        $includeMap = $transformer->getIncludeMap();

        // TODO: Determine what includes we actually want
        $wantIncludes = $defaultIncludes;

        // Add includes to the payload
        foreach ($wantIncludes as $includeKey) {
            $data[$includeKey] = $this->serialize(
                $this->callTransformerIncludeMethodFor($transformer, $includeKey, $item)
            );
        }

        // If defaultProps is defined, we will only return these props by default
        $defaultProps = $transformer->getDefaultProps();

        // Filter the sparse field-set
        // To determine the props that we want to return, we need to remove the requested includes
        // TODO: Determine if includes indicates props to return
        // TODO: This is probably the wrong place for this as we might filter out our inclusions?
        $wantProps = $defaultProps;
        if (!empty($wantProps)) {
            $data = array_filter($data, function ($key) use ($wantProps) {
                return \in_array($key, $wantProps, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $data;
    }

    protected function callTransformerIncludeMethodFor($transformer, $includeKey, $item)
    {
        return $transformer->{'include' . StrHelper::studlyCase($includeKey)}($item);
    }

    protected function serializeItem(Scope $scope)
    {
        $resource = $scope->getResource();

        return $this->serializer->item(
            $resource->getResourceKey(),
            $this->transformItem($resource->getData(), $resource->getTransformer())
        );
    }

}
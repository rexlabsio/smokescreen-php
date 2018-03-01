<?php


namespace Rexlabs\Smokescreen\Scope;


use Rexlabs\Smokescreen\Exception\InvalidTransformerException;
use Rexlabs\Smokescreen\Helpers\StrHelper;
use Rexlabs\Smokescreen\Relations\RelationLoaderInterface;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;

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
     */
    protected function serialize(Scope $scope): array
    {
        // Relationship loading
        $this->loadRelations($scope->getResource());

        // Build the output by recursively transforming each resource
        return $scope->isCollection() ?
            $this->serializeCollection($scope) : $this->serializeItem($scope);
    }

    protected function loadRelations(ResourceInterface $resource)
    {
        if ($this->loader !== null) {
            $this->loader->load($resource);
        }
    }

    protected function serializeCollection(Scope $scope)
    {
        $items = array_map(function($data) use ($scope) {

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

    /**
     * @param Item $item
     *
     * @return array
     */
    protected function transformItem(Item $item): array
    {
        $transformer = $item->getTransformer();

        return [];
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
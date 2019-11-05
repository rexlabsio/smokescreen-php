<?php

namespace Rexlabs\Smokescreen\Compositor;

/**
 * Class DefaultCompositor.
 */
class DefaultCompositor implements CompositorInterface
{
    /**
     * Compose included item by assigning data from current scope to the
     * transformed root or parent node.
     *
     * If the parent scope is a collection parent data is from one transformed
     * node of the collection.
     *
     * Order of include tree operations:
     *   - all nodes transformed
     *   - leaf scope serialized
     *   - leaf scope composed with parent / root node
     *   - parent scope serialized (will combine collection nodes)
     *   - repeat
     *
     * @param array      $rootData   Mutate to add data to root node (not yet serialized)
     * @param array      $parentData Mutate to add data to parent node (not yet serialized)
     * @param string     $key        Include key for this nested resource
     * @param array|null $itemData   Serialized scope data
     *
     * @return void
     */
    public function composeIncludedItem(array &$rootData, array &$parentData, string $key, $itemData)
    {
        $parentData[$key] = $itemData;
    }

    /**
     * Compose included collection by assigning data from current scope to the
     * transformed root or parent node.
     *
     * If the parent scope is a collection parent data is from one transformed
     * node of the collection.
     *
     * Order of include tree operations:
     *   - all nodes transformed
     *   - leaf scope serialized
     *   - leaf scope composed with parent / root node
     *   - parent scope serialized (will combine collection nodes)
     *   - repeat
     *
     * @param array      $rootData       Mutate to add data to root node (not yet serialized)
     * @param array      $parentData     Mutate to add data to parent node (not yet serialized)
     * @param string     $key            Include key for this nested resource
     * @param array|null $collectionData Serialized scope data
     *
     * @return void
     */
    public function composeIncludedCollection(array &$rootData, array &$parentData, string $key, $collectionData)
    {
        $parentData[$key] = $collectionData;
    }
}

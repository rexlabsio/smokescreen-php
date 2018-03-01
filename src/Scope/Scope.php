<?php

namespace Rexlabs\Smokescreen\Scope;

use Rexlabs\Smokescreen\Includes\Includes;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;

/**
 * Scopes hold the current resource being transformed, a possible reference to the parent scope
 * the resource (which points to the data and transformer), and the includes for this scope.
 * @package Rexlabs\Smokescreen\Scope
 */
class Scope
{
    /** @var ResourceInterface */
    protected $resource;

    /** @var Includes */
    protected $includes;

    public function __construct(ResourceInterface $resource, Includes $includes, Scope $parent = null)
    {
        $this->resource = $resource;
        $this->includes = $includes;
    }

    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    public function isCollection(): bool
    {
        return ($this->resource instanceof Collection);
    }

    public function isItem(): bool
    {
        return ($this->resource instanceof Item);
    }

    public function getIncludes(): Includes
    {
        return $this->includes;
    }

    public function setIncludes(Includes $includes)
    {
        $this->includes = $includes;

        return $this;
    }

    /**
     * Return the parent Scope object (if exists)
     * @return Scope|null
     */
//    public function parent()
//    {
//        return $this->parent;
//    }

    /**
     * Get the first scope in the chain
     * @return Scope
     */
//    public function root(): Scope
//    {
//        $scope = $this;
//        while (($parent = $scope->parent()) !== null) {
//            $scope = $parent;
//        }
//
//        return $scope;
//    }

    /**
     * An array containing root scope, and every parent to the current Scope object ($this)
     * @return Scope[]|array
     */
//    public function parentChain(): array
//    {
//        $scopes = [
//            $scope = $this,
//        ];
//
//        while (($scope = $scope->parent()) !== null) {
//            $scopes[] = $scope;
//        }
//
//        // Return scope list starting from root scope to this scope
//        return array_reverse($scopes);
//    }
}
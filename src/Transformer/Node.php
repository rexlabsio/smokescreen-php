<?php

namespace Rexlabs\Smokescreen\Transformer;

use Rexlabs\Smokescreen\Resource\ResourceInterface;

/**
 * Class Node
 *
 * @package Rexlabs\Smokescreen\Transformer
 */
class Node
{
    /**
     * @var Scope
     */
    private $containingScope;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var array|null
     */
    private $transformedData;

    /**
     * @var Scope[]
     */
    private $includedScopes;

    /**
     * Node constructor.
     *
     * @param Scope $containingScope
     * @param array|null $data Subset of resource data eg 1 item of a collection
     */
    public function __construct(Scope $containingScope, $data)
    {
        $this->containingScope = $containingScope;
        $this->data            = $data;
        $this->includedScopes  = [];
    }

    /**
     * @return Scope
     */
    public function getContainingScope(): Scope
    {
        return $this->containingScope;
    }

    /**
     * @return mixed|ResourceInterface
     */
    public function getScopeResource()
    {
        return $this->containingScope->resource();
    }

    /**
     * @return mixed|TransformerInterface|null
     */
    public function getTransformer()
    {
        return $this->containingScope->transformer();
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array|null
     */
    public function getTransformedData()
    {
        return $this->transformedData;
    }

    /**
     * @param array $transformedData
     * @return void
     */
    public function setTransformedData($transformedData)
    {
        $this->transformedData = $transformedData;
    }

    /**
     * @return Scope[]
     */
    public function getIncludedScopes(): array
    {
        return $this->includedScopes;
    }

    /**
     * @param Scope[] $scopes
     * @return void
     */
    public function setIncludedScopes(array $scopes)
    {
        $this->includedScopes = $scopes;
    }
}

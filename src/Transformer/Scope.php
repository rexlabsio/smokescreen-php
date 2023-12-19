<?php

namespace Rexlabs\Smokescreen\Transformer;

use Rexlabs\Smokescreen\Includes\Includes;
use Rexlabs\Smokescreen\Resource\ResourceInterface;

class Scope
{
    /** @var mixed|ResourceInterface */
    protected $resource;

    /** @var Includes */
    protected $includes;

    /** @var Scope|null */
    protected $parent;

    /**
     * Scope constructor.
     *
     * @param ResourceInterface|mixed $resource
     * @param Includes                $includes
     * @param Scope|null              $parent
     */
    public function __construct($resource, Includes $includes, self $parent = null)
    {
        $this->resource = $resource;
        $this->includes = $includes;
        $this->parent = $parent;
    }

    /**
     * @return TransformerInterface|mixed|null
     */
    public function transformer()
    {
        return $this->resource instanceof ResourceInterface ?
           $this->resource->getTransformer() : null;
    }

    /**
     * @return Includes
     */
    public function includes(): Includes
    {
        return $this->includes;
    }

    /**
     * @param Includes $includes
     */
    public function setIncludes(Includes $includes)
    {
        $this->includes = $includes;
    }

    /**
     * @return mixed|ResourceInterface
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * @param mixed|ResourceInterface $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function defaultIncludeKeys(): array
    {
        $defaultIncludeKeys = [];
        if (($transformer = $this->transformer()) !== null && ($transformer instanceof  TransformerInterface)) {
            $defaultIncludeKeys = $transformer->getDefaultIncludes();
        }

        return $defaultIncludeKeys;
    }

    /**
     * List of array keys identifying the available includes for this resource.
     *
     * @return array
     */
    public function availableIncludeKeys(): array
    {
        $availableKeys = [];
        if (($transformer = $this->transformer()) !== null && ($transformer instanceof  TransformerInterface)) {
            $availableKeys = $transformer->getAvailableIncludes();
        }

        return $availableKeys;
    }

    /**
     * The include keys that were requested.
     *
     * @return array
     */
    public function requestedIncludeKeys(): array
    {
        return $this->includes()->baseKeys();
    }

    /**
     * The include keys that were either requested or (if empty) the ones
     * that are are enabled by default.
     *
     * @return array
     */
    public function includeKeys(): array
    {
        // Wanted includes is a either the explicit includes requested, or the defaults for the transformer.
        return $this->requestedIncludeKeys() ?: $this->defaultIncludeKeys();
    }

    public function includeMap(): array
    {
        $map = [];
        if (($transformer = $this->transformer()) !== null && ($transformer instanceof  TransformerInterface)) {
            $map = $transformer->getIncludeMap();
        }

        return $map;
    }

    /**
     * @param $key
     *
     * @return array|null
     */
    public function includeDefinitionFor($key): ?array
    {
        return $this->includeMap()[$key] ?? null;
    }

    /**
     * A list of include keys that were requested and are available for
     * usage (eg. they are declared in the transformer).
     *
     * @return array
     */
    public function resolvedIncludeKeys(): array
    {
        $availableIncludeKeys = $this->availableIncludeKeys();

        return array_filter($this->includeKeys(), function ($includeKey) use ($availableIncludeKeys) {
            return \in_array($includeKey, $availableIncludeKeys, true);
        });
    }

    /**
     * Get a list of relationship keys for all of the includes which
     * have been resolved.
     *
     * @return array
     */
    public function resolvedRelationshipKeys(): array
    {
        $includeMap = $this->includeMap();

        $keys = [];
        foreach ($this->resolvedIncludeKeys() as $includeKey) {
            $relations = $includeMap[$includeKey]['relation'] ?? [];
            if (\count($relations) > 0) {
                array_push($keys, ...$relations);
            }
        }

        return array_unique($keys);
    }

    public function filterProps(): array
    {
        // We can consider our props anything that has not been mapped.
        $resolvedIncludeKeys = $this->resolvedIncludeKeys();
        $keys = array_filter($this->includeKeys(), function ($includeKey) use ($resolvedIncludeKeys) {
            return !\in_array($includeKey, $resolvedIncludeKeys, true);
        });

        // Were any filter props explicitly provided?
        // If not, see if defaults were provided from the transformer.
        if (empty($keys) && ($transformer = $this->transformer()) !== null) {
            // No explicit props provided
            $defaultProps = $transformer->getDefaultProps();
            if (!empty($defaultProps)) {
                $keys = $defaultProps;
            }
        }

        return $keys;
    }

    /**
     * @return null|Scope
     */
    public function parent()
    {
        return $this->parent;
    }

    /**
     * Apply the scope's sparse field-set to the given data.
     *
     * @param array $data
     *
     * @return array
     */
    public function filterData(array $data)
    {
        // Filter the sparse field-set if we have a specific list of properties
        // defined that we want.
        $filterProps = $this->filterProps();
        if (!empty($filterProps)) {
            $filteredData = array_filter($data, function ($key) use ($filterProps) {
                return \in_array($key, $filterProps, true);
            }, ARRAY_FILTER_USE_KEY);

            // We must always have some data after filtering, so if our filtered
            // data is empty, we should just ignore it ...
            if (!empty($filteredData)) {
                $data = $filteredData;
            }
        }

        return $data;
    }
}

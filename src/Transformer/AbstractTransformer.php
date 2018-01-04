<?php
namespace RexSoftware\Smokescreen\Transformer;

use RexSoftware\Smokescreen\Helpers\StrHelper;
use RexSoftware\Smokescreen\Resource\Collection;
use RexSoftware\Smokescreen\Resource\Item;

class AbstractTransformer implements TransformerInterface
{
//    protected $autoIncludes = true;

    /** @var array The list of available includes */
    protected $includes = [];

    /** @var array The default properties to return (if blank, all props from transform() are returned) */
    protected $defaultProps = [];

    /** @var null|array The $includes property is processed and turned into a map */
    protected $cachedIncludeMap;

//    protected $scope;

    /**
     * @inheritdoc
     */
    public function getAvailableIncludes(): array
    {
        return array_keys($this->getCachedIncludeMap());
    }

    /**
     * Return a cached version of the include map
     * @see AbstractTransformer::getIncludeMap()
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getCachedIncludeMap(): array
    {
        if ($this->cachedIncludeMap === null) {
            $this->cachedIncludeMap = $this->getIncludeMap();
        }

        return $this->cachedIncludeMap;
    }

    /**
     * Process the $includes property and convert the directives into a map
     * indexed by the include key, and specifying: default, relation, method.
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getIncludeMap(): array
    {
        $map = [];

        foreach ($this->includes as $includeKey => $definition) {
            if (\is_int($includeKey)) {
                $includeKey = $definition;
                $definition = null;
            }

            $settings = [];
            if (!empty($definition)) {
                $parts = preg_split('/\s*\|\s*/', $definition);
                foreach ($parts as $part) {
                    if (!preg_match('/^([^:]+)(:(.+))?$/', $part, $match)) {
                        continue;
                    }
                    // If only list() was viable ...
                    $directive = $match[1];
                    $val = $match[3] ?? null;
                    switch ($directive) {
                        case 'default':
                            $settings['default'] = true;
                            break;
                        case 'relation':
                            $settings['relation'] = !empty($val) ?
                                preg_split('/s*,\s*/', $val) :
                                [$includeKey];
                            break;
                        case 'method':
                            if (!empty($val)) {
                                $settings['method'] = $val;
                            }
                            break;
                        default:
                            throw new \InvalidArgumentException("Invalid key '{$directive}' for {$includeKey}");
                    }
                }

                if (!isset($settings['relation'])) {
                    $settings['relation'] = [];
                }
                if (!isset($settings['method'])) {
                    $settings['method'] = 'include' . StrHelper::studlyCase($includeKey);
                }
                if (!isset($settings['default'])) {
                    $settings['default'] = false;
                }

                $map[$includeKey] = $settings;
            }
        }

        return $map;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultIncludes(): array
    {
        return array_filter(array_keys($this->getCachedIncludeMap()), function ($includeKey) {
            return $this->getCachedIncludeMap()[$includeKey]['default'];
        });
    }

    /**
     * @inheritdoc
     */
    public function getRelationships(): array
    {
        // TODO: not sure if this works
        return array_flatten(array_map(function ($includeKey, $settings) {
            return [$includeKey => $settings['relation']];
        }, array_keys($this->getCachedIncludeMap()), array_values($this->getCachedIncludeMap())));
    }

    /**
     * @inheritdoc
     */
    public function getDefaultProps(): array
    {
        return $this->defaultProps;
    }

//    /**
//     * @inheritdoc
//     */
//    public function getScope(): Scope
//    {
//        return $this->scope;
//    }

    /**
     * Create a new Item resource
     * @param mixed $data
     * @param mixed|null $transformer
     * @param string|null $resourceKey
     * @return Item
     */
    public function item($data, $transformer = null, $resourceKey = null): Item
    {
        return new Item($data, $transformer, $resourceKey);
    }

    /**
     * Create new Collection resource
     * @param mixed $data
     * @param mixed|null $transformer
     * @param string|null $resourceKey
     * @return Collection
     */
    public function collection($data, $transformer = null, $resourceKey = null): Collection
    {
        return new Collection($data, $transformer, $resourceKey);
    }
}
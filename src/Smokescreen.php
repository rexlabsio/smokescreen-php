<?php

namespace Rexlabs\Smokescreen;

use Rexlabs\Smokescreen\Compositor\CompositorInterface;
use Rexlabs\Smokescreen\Compositor\DefaultCompositor;
use Rexlabs\Smokescreen\Exception\MissingResourceException;
use Rexlabs\Smokescreen\Helpers\JsonHelper;
use Rexlabs\Smokescreen\Includes\IncludeParser;
use Rexlabs\Smokescreen\Includes\IncludeParserInterface;
use Rexlabs\Smokescreen\Includes\Includes;
use Rexlabs\Smokescreen\Relations\RelationLoaderInterface;
use Rexlabs\Smokescreen\Resource\Collection;
use Rexlabs\Smokescreen\Resource\Item;
use Rexlabs\Smokescreen\Resource\ResourceInterface;
use Rexlabs\Smokescreen\Serializer\DefaultSerializer;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\Pipeline;
use Rexlabs\Smokescreen\Transformer\Scope;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerResolverInterface;

/**
 * Smokescreen is a library for transforming and serializing data - typically RESTful API output.
 */
class Smokescreen implements \JsonSerializable
{
    /** @var ResourceInterface Item or Collection to be transformed */
    protected $resource;

    /** @var SerializerInterface|null */
    protected $serializer;

    /** @var CompositorInterface|null */
    protected $compositor;

    /** @var IncludeParserInterface */
    protected $includeParser;

    /** @var RelationLoaderInterface */
    protected $relationLoader;

    /** @var Includes */
    protected $includes;

    /** @var TransformerResolverInterface */
    protected $transformerResolver;

    /**
     * Return the current resource.
     *
     * @return ResourceInterface|mixed|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set the resource to be transformed.
     *
     * @param ResourceInterface|mixed|null $resource
     *
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set the resource item to be transformed.
     *
     * @param mixed                           $data
     * @param TransformerInterface|mixed|null $transformer
     * @param string|null                     $key
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     *
     * @return $this
     */
    public function item($data, $transformer = null, $key = null)
    {
        $this->setResource(new Item($data, $transformer, $key));

        return $this;
    }

    /**
     * Set the resource collection to be transformed.
     *
     * @param mixed                           $data
     * @param TransformerInterface|mixed|null $transformer
     * @param string|null                     $key
     * @param callable|null                   $callback
     *
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     *
     * @return $this
     */
    public function collection($data, TransformerInterface $transformer = null, $key = null, callable $callback = null)
    {
        $this->setResource(new Collection($data, $transformer, $key));
        if ($callback !== null) {
            $callback($this->resource);
        }

        return $this;
    }

    /**
     * Gets the transformer to be used to transform the resource ... later.
     *
     * @throws MissingResourceException
     *
     * @return TransformerInterface|mixed|null
     */
    public function getTransformer()
    {
        if (!$this->resource) {
            throw new MissingResourceException('Resource must be specified before setting a transformer');
        }

        return $this->resource->getTransformer();
    }

    /**
     * Sets the transformer to be used to transform the resource ... later.
     *
     * @param TransformerInterface|mixed|null $transformer
     *
     * @throws MissingResourceException
     *
     * @return $this
     */
    public function setTransformer($transformer = null)
    {
        if (!$this->resource) {
            throw new MissingResourceException('Resource must be specified before setting a transformer');
        }
        $this->resource->setTransformer($transformer);

        return $this;
    }

    /**
     * Returns an object (stdClass) representation of the transformed/serialized data.
     *
     *@throws \Rexlabs\Smokescreen\Exception\UnhandledResourceTypeException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     *
     * @return \stdClass
     */
    public function toObject(): \stdClass
    {
        return (object) json_decode($this->toJson());
    }

    /**
     * Outputs a JSON string of the resulting transformed and serialized data.
     *
     * @param int $options
     *
     *@throws \Rexlabs\Smokescreen\Exception\UnhandledResourceTypeException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\JsonEncodeException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     *
     * @return string
     */
    public function toJson($options = 0): string
    {
        return JsonHelper::encode($this->jsonSerialize(), $options);
    }

    /**
     * Output the transformed and serialized data as an array.
     * Implements PHP's JsonSerializable interface.
     *
     * @throws \Rexlabs\Smokescreen\Exception\UnhandledResourceTypeException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     *
     * @return array
     *
     * @see Smokescreen::toArray()
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Return the transformed data as an array.
     *
     *@throws \Rexlabs\Smokescreen\Exception\UnhandledResourceTypeException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidTransformerException
     * @throws \Rexlabs\Smokescreen\Exception\MissingResourceException
     * @throws \Rexlabs\Smokescreen\Exception\IncludeException
     * @throws \Rexlabs\Smokescreen\Exception\InvalidSerializerException
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource) {
            throw new MissingResourceException('No resource has been defined to transform');
        }

        return (array) $this->transform();
    }

    /**
     * @throws Exception\IncludeException
     *
     * @return array|null
     */
    protected function transform()
    {
        $scope = new Scope($this->resource, $this->getIncludes());
        $pipeline = new Pipeline();
        $pipeline->setSerializer($this->getSerializer());
        $pipeline->setCompositor($this->getCompositor());

        if (($transformerResolver = $this->getTransformerResolver()) !== null) {
            $pipeline->setTransformerResolver($transformerResolver);
        }

        if (($relationLoader = $this->getRelationLoader()) !== null) {
            $pipeline->setRelationLoader($relationLoader);
        }

        return $pipeline->run($scope);
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer ?? new DefaultSerializer();
    }

    /**
     * @return CompositorInterface
     */
    public function getCompositor(): CompositorInterface
    {
        return $this->compositor ?? new DefaultCompositor();
    }

    /**
     * Set the serializer which will be used to output the transformed resource.
     *
     * @param SerializerInterface|null $serializer
     *
     * @return $this
     */
    public function setSerializer(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Set the compositor which will be used to compose included nodes.
     *
     * @param CompositorInterface|null $compositor
     *
     * @return $this
     */
    public function setCompositor(CompositorInterface $compositor = null)
    {
        $this->compositor = $compositor;

        return $this;
    }

    /**
     * Get the current includes object.
     *
     * @return Includes
     */
    public function getIncludes(): Includes
    {
        return $this->includes ?? new Includes();
    }

    /**
     * Set the Includes object used for determining included resources.
     *
     * @param Includes $includes
     *
     * @return $this
     */
    public function setIncludes(Includes $includes)
    {
        $this->includes = $includes;

        return $this;
    }

    /**
     * Parse the given string to generate a new Includes object.
     *
     * @param string $str
     *
     * @return $this
     */
    public function parseIncludes($str)
    {
        $this->includes = $this->getIncludeParser()->parse(!empty($str) ? $str : '');

        return $this;
    }

    /**
     * Return the include parser object.
     * If not set explicitly via setIncludeParser(), it will return the default IncludeParser object.
     *
     * @return IncludeParserInterface
     *
     * @see Smokescreen::setIncludeParser()
     */
    public function getIncludeParser(): IncludeParserInterface
    {
        return $this->includeParser ?? new IncludeParser();
    }

    /**
     * Set the include parser to handle converting a string to an Includes object.
     *
     * @param IncludeParserInterface $includeParser
     *
     * @return $this
     */
    public function setIncludeParser(IncludeParserInterface $includeParser)
    {
        $this->includeParser = $includeParser;

        return $this;
    }

    /**
     * Get the current relation loader.
     *
     * @return RelationLoaderInterface|null
     */
    public function getRelationLoader()
    {
        return $this->relationLoader;
    }

    /**
     * Set the relationship loader.
     *
     * @param RelationLoaderInterface $relationLoader
     *
     * @return $this
     */
    public function setRelationLoader(RelationLoaderInterface $relationLoader)
    {
        $this->relationLoader = $relationLoader;

        return $this;
    }

    /**
     * Returns true if a RelationLoaderInterface object has been defined.
     *
     * @return bool
     */
    public function hasRelationLoader(): bool
    {
        return $this->relationLoader !== null;
    }

    /**
     * @return TransformerResolverInterface|null
     */
    public function getTransformerResolver()
    {
        return $this->transformerResolver;
    }

    /**
     * Set the transformer resolve to user.
     *
     * @param TransformerResolverInterface|null $transformerResolver
     *
     * @return $this
     */
    public function setTransformerResolver(TransformerResolverInterface $transformerResolver = null)
    {
        $this->transformerResolver = $transformerResolver;

        return $this;
    }
}

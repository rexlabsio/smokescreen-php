<?php

namespace Rexlabs\Smokescreen\Resource;

use Rexlabs\Smokescreen\Compositor\CompositorInterface;
use Rexlabs\Smokescreen\Serializer\SerializerInterface;
use Rexlabs\Smokescreen\Transformer\TransformerInterface;

interface ResourceInterface
{
    /**
     * Get the resource key.
     *
     * @return string|null
     */
    public function getResourceKey();

    /**
     * Get the data.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Get the transformer.
     *
     * @return TransformerInterface|callable|null
     */
    public function getTransformer();

    /**
     * @return bool
     */
    public function hasTransformer(): bool;

    /**
     * Set the data.
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data);

    /**
     * Set the transformer.
     *
     * @param TransformerInterface|callable|null
     *
     * @return $this
     */
    public function setTransformer($transformer);

    /**
     * An array of relationship keys.
     *
     * @return array
     */
    public function getRelationships(): array;

    /**
     * Get the serializer.
     *
     * @return SerializerInterface|callable|false|null
     */
    public function getSerializer();

    /**
     * @return bool
     */
    public function hasSerializer(): bool;

    /**
     * Set the serializer.
     *
     * @param SerializerInterface|callable|false|null
     *
     * @return $this
     */
    public function setSerializer($serializer);

    /**
     * Get the compositor.
     *
     * @return CompositorInterface|callable|null
     */
    public function getCompositor();

    /**
     * @return bool
     */
    public function hasCompositor(): bool;

    /**
     * Set the compositor.
     *
     * @param CompositorInterface|callable|null
     *
     * @return $this
     */
    public function setCompositor($compositor);
}

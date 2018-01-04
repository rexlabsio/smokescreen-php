<?php

namespace RexSoftware\Smokescreen\Resource;

use RexSoftware\Smokescreen\Transformer\TransformerInterface;

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
     * @return boolean
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
     * @param callable|TransformerInterface
     *
     * @return $this
     */
    public function setTransformer($transformer);

    /**
     * An array of relationship keys
     * @return mixed
     */
    public function getRelationships(): array;
}
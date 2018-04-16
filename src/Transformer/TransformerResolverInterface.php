<?php

namespace Rexlabs\Smokescreen\Transformer;

use Rexlabs\Smokescreen\Resource\ResourceInterface;

interface TransformerResolverInterface
{
    /**
     * Determines the Transformer object to be used for a particular resource.
     *
     * @param ResourceInterface $resource
     *
     * @return TransformerInterface|mixed|null
     */
    public function resolve(ResourceInterface $resource);
}

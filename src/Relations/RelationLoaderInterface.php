<?php

namespace Rexlabs\Smokescreen\Relations;

use Rexlabs\Smokescreen\Resource\ResourceInterface;

interface RelationLoaderInterface
{
    public function load(ResourceInterface $resource);
}

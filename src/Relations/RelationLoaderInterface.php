<?php
namespace RexSoftware\Smokescreen\Relations;

use RexSoftware\Smokescreen\Resource\ResourceInterface;

interface RelationLoaderInterface
{
    public function load(ResourceInterface $resource);
}
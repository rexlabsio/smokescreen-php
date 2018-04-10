<?php

namespace Rexlabs\Smokescreen\Includes;

use Rexlabs\Smokescreen\Definition\AbstractDefinition;

class IncludeDefinition extends AbstractDefinition
{
    public function relation(): array
    {
        $relation = $this->get('relation', []);
        return !\is_array($relation) ?
            preg_split('/\s*,\s*', $relation) :
            $relation;
    }

    public function method()
    {
        return $this->get('method');
    }

    public function isDefault()
    {
        return $this->has('default');
    }
}
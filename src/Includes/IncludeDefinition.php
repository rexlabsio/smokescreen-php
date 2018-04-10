<?php

namespace Rexlabs\Smokescreen\Includes;

use Rexlabs\Smokescreen\Definition\AbstractDefinition;

class IncludeDefinition extends AbstractDefinition
{
    public function relation(): array
    {
        $relation = $this->get('relation', []);
        if (!\is_array($relation)) {
            $relation = (array) preg_split('/\s*,\s*', $relation);
        }

        return $relation;
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

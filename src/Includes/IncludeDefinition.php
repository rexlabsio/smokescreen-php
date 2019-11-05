<?php

namespace Rexlabs\Smokescreen\Includes;

use function is_array;
use Rexlabs\Smokescreen\Definition\AbstractDefinition;

class IncludeDefinition extends AbstractDefinition
{
    public function relation(): array
    {
        $relation = $this->get('relation', []);
        if (!is_array($relation)) {
            $relation = (array) preg_split('/\s*,\s*', $relation);
        }

        return $relation;
    }

    public function method()
    {
        return $this->get('method');
    }

    public function isDefault(): bool
    {
        return $this->has('default');
    }
}

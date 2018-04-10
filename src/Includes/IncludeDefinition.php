<?php

namespace Rexlabs\Smokescreen\Includes;

use Rexlabs\Smokescreen\Definition\AbstractDefinition;

class IncludeDefinition extends AbstractDefinition
{
    public function relation(): array
    {
        return $this->get('relation', []);
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
<?php

namespace Rexlabs\Smokescreen\Transformer\Props;

use Rexlabs\Smokescreen\Definition\AbstractDefinition;

class PropDefinition extends AbstractDefinition
{
    public function type()
    {
        return $this->get('type');
    }

    public function mapKey()
    {
        return $this->get('map', $this->key);
    }
}
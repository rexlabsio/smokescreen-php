<?php

namespace Rexlabs\Smokescreen\Definition;

class AbstractDefinition
{
    protected $key;
    protected $definition;

    public function __construct($key, array $definition = [])
    {
        $this->setKey($key);
        $this->setDefinition($definition);
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function key()
    {
        return $this->key;
    }

    public function setDefinition(array $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    public function hasDirective(string $directive)
    {
        return array_key_exists($directive, $this->definition);
    }

    public function get(string $directive, $default = null)
    {
        return $this->hasDirective($directive) ?
            $this->definition[$directive] :
            $default;
    }

    public function set(string $directive, $value)
    {
        $this->definition[$directive] = $value;

        return $this;
    }

    public function toArray()
    {
        return $this->definition;
    }
}
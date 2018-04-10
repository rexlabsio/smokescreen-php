<?php

namespace Rexlabs\Smokescreen\Definition;

class AbstractDefinition
{
    /**
     * An optional key which identifies this definition
     *
     * @var string|null
     */
    protected $key;

    /**
     * An associative-array of definition settings.
     *
     * @var array
     */
    protected $definition;

    /**
     * AbstractDefinition constructor.
     *
     * @param string|null $key
     * @param array       $definition
     */
    public function __construct($key, array $definition = [])
    {
        $this->setKey($key);
        $this->setDefinition($definition);
    }

    /**
     * @param string|null $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return null|string
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Replace the definition settings with the given associative array.
     *
     * @param array $definition
     *
     * @return $this
     */
    public function setDefinition(array $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * Returns true if the given directive exists (even if it is null)
     *
     * @param string $directive
     *
     * @return bool
     */
    public function has(string $directive)
    {
        return array_key_exists($directive, $this->definition);
    }

    /**
     * Get the value for a directive if it exists, otherwise return a default.
     *
     * @param string     $directive
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get(string $directive, $default = null)
    {
        return $this->has($directive) ? $this->definition[$directive] : $default;
    }

    /**
     * Set a value for a directive.
     *
     * @param string $directive
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $directive, $value)
    {
        $this->definition[$directive] = $value;

        return $this;
    }

    /**
     * Get the underlying associative array definition.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->definition;
    }
}
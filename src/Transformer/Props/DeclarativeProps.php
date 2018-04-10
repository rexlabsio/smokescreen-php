<?php

namespace Rexlabs\Smokescreen\Transformer\Props;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Rexlabs\Smokescreen\Definition\DefinitionParser;
use Rexlabs\Smokescreen\Exception\InvalidDefinitionException;
use Rexlabs\Smokescreen\Helpers\ArrayHelper;
use Rexlabs\Smokescreen\Helpers\StrHelper;

trait DeclarativeProps
{
    /** @var array */
    protected $props = [];

    /** @var string Example: 2018-03-08 */
    protected $dateFormat = 'Y-m-d';

    /** @var string Example: 2018-03-08T19:11:11.234+00:00 */
    protected $dateTimeFormat = 'Y-m-d\TH:i:s.vP';

    /** @var mixed|null Optionally set a default timezone */
    protected $defaultTimezone;

    /** @var DefinitionParser|null */
    protected $definitionParser;

    public function getProps(): array
    {
        return $this->props;
    }

    protected function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    protected function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    protected function getDefaultTimezone(): string
    {
        return $this->defaultTimezone;
    }

    /**
     * Helper method for returning formatted properties from a source object
     * or array.
     *
     * @param \ArrayAccess|array $model
     * @param array              $props
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function withProps($model, array $props): array
    {
        if (!(\is_array($model) || $model instanceof \ArrayAccess)) {
            throw new \InvalidArgumentException('Expect array or object implementing \ArrayAccess');
        }

        // Given an array of props (which may include a definition value), we
        // cycle through each, resolve the value from the model, and apply any
        // conversions to the value as necessary.
        $data = [];
        foreach ($props as $key => $definition) {
            if (\is_int($key)) {
                // This isn't a key => value pair, so there is no definition.
                $key = $definition;
                $definition = null;
            }

            // Convert property to a snake-case version.
            // It may be a nested (dot-notation) key.
            $propKey = StrHelper::snakeCase($key);
            if ($definition instanceof \Closure) {
                // If the definition is a function, execute it on the value
                $value = $definition->bindTo($this)($model, $propKey);
            } else {
                // Format the field according to the definition
                $value = $this->getPropValue($model, $this->parsePropDefinition($propKey, $definition));
            }

            // Set the prop value in the array
            ArrayHelper::mutate($data, $propKey, $value);
        }

        return $data;
    }


    /**
     * @param \ArrayAccess|array $model
     * @param PropDefinition     $propDefinition
     *
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    protected function getPropValue($model, PropDefinition $propDefinition)
    {
        // Get the model value via array access.
        $value = $model[$propDefinition->mapKey()] ?? null;

        // Format the value the property value.
        $value = $this->formatPropValue($value, $propDefinition);

        return $value;
    }

    /**
     * @param mixed          $value
     * @param PropDefinition $propDefinition
     *
     * @return array|string
     * @throws \InvalidArgumentException
     */
    protected function formatPropValue($value, $propDefinition)
    {
        if (!$propDefinition->type()) {
            return $value;
        }

        // Built-in types.
        // Cast the value according to 'type'
        switch ($propDefinition->type()) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'real':
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'array':
                return (array)$value;
            case 'date':
                return $this->formatDate($value, $propDefinition);
            case 'datetime':
                return $this->formatDatetime($value, $propDefinition);
            case 'datetime_utc':
                return $this->formatDatetimeUtc($value, $propDefinition);
            default:
                // Fall through
                break;

        }

        // As a final attempt, try to locate a matching method on the class that
        // is prefixed with 'format'.
        $method = 'format' . StrHelper::studlyCase($propDefinition->type());
        if (method_exists($this, $method)) {
            return $this->$method($value, $propDefinition);
        }

        throw new InvalidDefinitionException("Unsupported format type: {$propDefinition->type()}");
    }

    protected function getDefinitionParser(): DefinitionParser
    {
        if ($this->definitionParser === null) {
            $this->definitionParser = new DefinitionParser();
            $this->definitionParser->addShortKeys('type', [
                'int',
                'integer',
                'real',
                'float',
                'double',
                'string',
                'bool',
                'boolean',
                'array',
                'date',
                'datetime',
                'datetime_utc',
            ]);
        }

        return $this->definitionParser;
    }

    /**
     * @param string       $propKey
     * @param string|mixed $definition
     *
     * @return PropDefinition
     * @throws \Rexlabs\Smokescreen\Exception\ParseDefinitionException
     */
    protected function parsePropDefinition(string $propKey, $definition): PropDefinition
    {
        return new PropDefinition($propKey, $this->getDefinitionParser()
            ->parse($definition));
    }

    /**
     * Given a date object, return a new date object with the specified timezone.
     * This method ensures that the original date object is not mutated.
     *
     * @param DateTimeInterface   $date
     * @param DateTimeZone|string $timezone
     *
     * @return DateTimeInterface|DateTime|DateTimeImmutable
     */
    protected function convertTimeZone(DateTimeInterface $date, $timezone)
    {
        $timezone = ($timezone instanceof DateTimeZone) ? $timezone : new DateTimeZone($timezone);

        if ($date->getTimezone() !== $timezone) {
            if ($date instanceof DateTime) {
                // DateTime is not immutable, so make a copy
                $date = (clone $date);
                $date->setTimezone($timezone);
            } elseif ($date instanceof DateTimeImmutable) {
                $date = $date->setTimezone($timezone);
            }
        }

        return $date;
    }

    /**
     * Format a date object.
     *
     * @param DateTimeInterface $date
     * @param PropDefinition    $definition
     *
     * @return string
     */
    protected function formatDatetime(DateTimeInterface $date, PropDefinition $definition): string
    {
        $timezone = $definition->get('timezone', $this->getDefaultTimezone());
        if ($timezone !== null) {
            $date = $this->convertTimeZone($date, $timezone);
        }

        return $date->format($definition->get('format', $this->getDateTimeFormat()));
    }

    /**
     * Shortcut method for converting a date to UTC and returning the formatted string.
     *
     * @param DateTimeInterface $date
     * @param PropDefinition    $propDefinition
     *
     * @return string
     * @see FormatsFields::formatDateTime()
     */
    protected function formatDatetimeUtc(DateTimeInterface $date, PropDefinition $propDefinition): string
    {
        return $this->formatDatetime($date, $propDefinition->set('timezone', 'UTC'));
    }

    /**
     * @param DateTimeInterface $date
     * @param PropDefinition    $propDefinition
     *
     * @return string
     */
    protected function formatDate(DateTimeInterface $date, PropDefinition $propDefinition): string
    {
        if (!$propDefinition->has('format')) {
            $propDefinition->set('format', $this->getDateFormat());
        }

        return $this->formatDatetime($date, $propDefinition);
    }
}
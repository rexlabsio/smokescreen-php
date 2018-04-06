<?php

namespace Rexlabs\Smokescreen\Transformer\Concerns;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Rexlabs\Smokescreen\Exception\ParseDefinitionException;
use Rexlabs\Smokescreen\Helpers\ArrayHelper;
use Rexlabs\Smokescreen\Helpers\StrHelper;

trait FormatProps
{
    /** @var array */
    protected $props = [];

    /** @var string Example: 2018-03-08 */
    protected $dateFormat = 'Y-m-d';

    /** @var string Example: 2018-03-08T19:11:11.234+00:00 */
    protected $dateTimeFormat = 'Y-m-d\TH:i:s.vP';

    /** @var mixed|null Optionally set a default timezone */
    protected $defaultTimezone;

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
            $prop = StrHelper::snakeCase($key);

            if ($definition instanceof \Closure) {
                // If the definition is a function, execute it on the value
                $value = $definition->bindTo($this)($model, $prop);
            } else {
                // Format the field according to the definition
                $settings = $definition !== null ? $this->parseDefinition($definition) : [];
                $value = $this->getPropValue($model, $prop, $settings);
            }

            // Set the prop value in the array
            ArrayHelper::mutate($data, $prop, $value);
        }

        return $data;
    }


    /**
     * @param \ArrayAccess|array $model
     * @param string             $prop
     * @param array              $settings
     *
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    protected function getPropValue($model, $prop, $settings)
    {
        // If a 'map' setting is provided, map to that key on the model instead.
        $mapKey = $settings['map'] ?? $prop;

        // Get the model value via array access.
        $value = $model[$mapKey] ?? null;

        // Format the value the property value.
        $value = $this->formatPropValue($value, $settings);

        return $value;
    }

    /**
     * @param $value
     * @param $settings
     *
     * @return array|string
     * @throws \InvalidArgumentException
     */
    protected function formatPropValue($value, $settings)
    {
        if (empty($settings['type'])) {
            return $value;
        }

        // Cast the value according to 'type' (if defined).
        switch ($settings['type']) {
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
                return $this->formatDate($value, $settings);
            case 'datetime':
                return $this->formatDateTime($value, $settings);
            default:
                // Fall through
                break;

        }

        // As a final attempt, try to locate a matching method on the class that
        // is prefixed with 'format'.
        $method = 'format' . StrHelper::studlyCase($settings['type']);
        if (method_exists($this, $method)) {
            return $this->$method($value, $settings);
        }

        throw new \InvalidArgumentException("Unsupported format type: {$settings['type']}");
    }

    /**
     * Parses a definition string into an array.
     * Supports a value like integer|arg1:val|arg2:val|arg3
     *
     * @param string $definition
     *
     * @return array
     * @throws \Rexlabs\Smokescreen\Exception\ParseDefinitionException
     */
    protected function parseDefinition(string $definition): array
    {
        $settings = [];
        $parts = preg_split('/\s*\|\s*/', $definition);
        foreach ($parts as $part) {
            // Each part may consist of "directive:value" or it may just be "directive".
            if (!preg_match('/^([^:]+)(:(.+))?$/', $part, $match)) {
                throw new ParseDefinitionException("Unable to parse field definition: $definition");
            }
            $directive = $match[1];
            $value = $match[3] ?? null;

            // As a short-cut, we will allow the type to be provided without a "type:" prefix.
            if (preg_match('/^(int|integer|real|float|double|string|bool|array|date|datetime)$/', $directive)) {
                if ($value !== null) {
                    // If a value was also provided, we'll store that in a separate entry.
                    $settings[StrHelper::snakeCase(strtolower($directive))] = $value;
                }

                $directive = 'type';
                $value = $part;
            }

            // Normalise our directive (as snake_case) and store the value.
            $settings[StrHelper::snakeCase(strtolower($directive))] = $value;
        }

        return $settings;
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
     * Format a date object
     *
     * @param DateTimeInterface $date
     * @param array             $settings
     *
     * @return string
     */
    protected function formatDateTime(DateTimeInterface $date, array $settings = []): string
    {
        $timezone = $settings['timezone'] ?? $this->getDefaultTimezone();
        if ($timezone !== null) {
            $date = $this->convertTimeZone($date, $timezone);
        }

        return $date->format($settings['format'] ?? $this->getDateTimeFormat());
    }

    /**
     * Shortcut method for converting a date to UTC and returning the formatted string.
     *
     * @param DateTimeInterface $date
     * @param array             $settings
     *
     * @return string
     * @see FormatsFields::formatDateTime()
     */
    protected function formatDateTimeUtc(DateTimeInterface $date, array $settings = []): string
    {
        $settings['timezone'] = 'UTC';
        return $this->formatDateTime($date, $settings);
    }

    /**
     * @param DateTimeInterface $date
     * @param array             $settings
     *
     * @return string
     */
    protected function formatDate(DateTimeInterface $date, array $settings = []): string
    {
        return $this->formatDateTime($date, $settings);
    }
}
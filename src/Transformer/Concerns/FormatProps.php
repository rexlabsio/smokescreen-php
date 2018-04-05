<?php

namespace Rexlabs\Smokescreen\Transformer\Concerns;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Rexlabs\Smokescreen\Helpers\ArrayHelper;
use Rexlabs\Smokescreen\Helpers\StrHelper;

trait FormatProps
{
    /** @var array  */
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

        $data = [];
        foreach ($props as $key => $definition) {
            if (is_numeric($key)) {
                // This isn't a key => value pair, so there is no definition.
                $key = $definition;
                $definition = null;
            }

            // Convert property to a snake-case version.
            // It may be a nested (dot-notation) key.
            $prop = StrHelper::snakeCase($key);

            // Get the model value via array access
            $value = $model[$prop] ?? null;

            if ($definition instanceof \Closure) {
                // If the definition is a function, execute it on the value
                $value = $definition($model, $prop, $definition, $value);
            } elseif ($definition !== null) {
                // Format the field according to the definition
                $value = $this->formatProp($definition, $value);
            }

            // Set the prop value in the array
            ArrayHelper::mutate($data, $prop, $value);
        }

        return $data;
    }

    /**
     * Format a date object
     *
     * @param DateTimeInterface|null   $date
     * @param string|null              $format
     * @param DateTimeZone|string|null $timezone
     *
     * @return null|string
     */
    protected function formatDateTime(?DateTimeInterface $date, ?string $format = null, $timezone = null): ?string
    {
        $output = null;

        if ($date !== null) {
            $tz = $timezone ?? $this->getDefaultTimezone();
            if ($tz !== null) {
                $date = $this->convertTimeZone($date, $timezone);
            }
            $output = $date->format($format ?? $this->getDateFormat());
        }

        return $output;
    }

    /**
     * Shortcut method for converting a date to UTC and returning the formatted string.
     *
     * @param DateTimeInterface|null   $date
     * @param string|null              $format
     *
     * @return null|string
     * @see FormatsFields::formatDateTime()
     */
    protected function formatDateTimeUtc(?DateTimeInterface $date, ?string $format = null): ?string
    {
        return $this->formatDateTime($date, $format, DateTimeZone::UTC);
    }

    /**
     * @param DateTimeInterface|null   $date
     * @param string|null              $format
     * @param DateTimeZone|string|null $timezone
     *
     * @return string|null
     */
    protected function formatDate(?DateTimeInterface $date, ?string $format = null, $timezone = null): ?string
    {
        $output = null;
        if ($date !== null) {
            $tz = $timezone ?? $this->getDefaultTimezone();
            if ($tz !== null) {
                $date = $this->convertTimeZone($date, $timezone);
            }
            $output = $date->format($format ?? $this->getDateFormat());
        }

        return $output;
    }

    /**
     * @param string $definition
     * @param mixed  $value
     *
     * @return mixed|null
     */
    protected function formatProp(string $definition, $value)
    {
        $parts = explode(':', $definition, 2);
        $type = $parts[0];
        $settings = $parts[1] ?? null;

        switch ($type) {
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
                // Settings may be a format string Eg. 'Y-m-d' or null
                return $this->formatDate($value, $settings);
            case 'datetime':
                // Settings may be a timezone:format string
                $parts = $settings !== null ? explode(':', $settings) : [];
                return $this->formatDateTime($value, $parts[1] ?: null, $parts[0] ?: null);
            default:
                break;
        }

        return $value;
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
}
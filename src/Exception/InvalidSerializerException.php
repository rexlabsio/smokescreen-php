<?php

namespace Rexlabs\Smokescreen\Exception;

use InvalidArgumentException;
use Throwable;

class InvalidSerializerException extends InvalidArgumentException
{
    const MESSAGE = 'Serializer must be one of: callable, SerializerInterface, false or null';

    /**
     * InvalidSerializerException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = self::MESSAGE, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

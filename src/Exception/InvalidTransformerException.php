<?php

namespace Rexlabs\Smokescreen\Exception;

use InvalidArgumentException;
use Throwable;

class InvalidTransformerException extends InvalidArgumentException
{
    const DEFAULT_MESSAGE = 'Transformer must be a callable or implement TransformerInterface';

    /**
     * InvalidTransformerException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message ?: self::DEFAULT_MESSAGE, $code, $previous);
    }
}

<?php

namespace Jinya\PDOx\Exceptions;

use Exception;
use JetBrains\PhpStorm\Pure;
use Throwable;

class InvalidQueryException extends Exception
{
    /**
     * InvalidQueryException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array<string, mixed> $errorInfo
     */
    #[Pure] public function __construct(string $message = "", int $code = 0, Throwable $previous = null, public array $errorInfo = [])
    {
        parent::__construct($message, $code, $previous);
    }
}
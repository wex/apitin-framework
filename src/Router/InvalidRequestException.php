<?php declare(strict_types = 1);

namespace Apitin\Router;

class InvalidRequestException extends RouterException
{
    public function __construct($message = "", $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
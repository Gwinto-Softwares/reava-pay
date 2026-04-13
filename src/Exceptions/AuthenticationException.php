<?php

namespace ReavaPay\Gwinto\Exceptions;

class AuthenticationException extends GwintoException
{
    public function __construct(string $message = 'Authentication failed')
    {
        parent::__construct($message, 401);
    }
}

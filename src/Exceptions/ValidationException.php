<?php

namespace ReavaPay\Gwinto\Exceptions;

class ValidationException extends GwintoException
{
    protected array $errors;

    public function __construct(string $message = 'Validation failed', array $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message, 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

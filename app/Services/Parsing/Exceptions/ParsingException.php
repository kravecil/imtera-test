<?php

namespace App\Services\Parsing\Exceptions;

use Exception;

class ParsingException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = '',
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = 'Ошибка парсинга страницы : ' . $message;

        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

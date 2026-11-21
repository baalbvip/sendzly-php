<?php

declare(strict_types=1);

namespace Sendzly;

use RuntimeException;

class SendzlyException extends RuntimeException
{
    public readonly int $statusCode;
    public readonly ?string $errorCode;
    public readonly array $responseBody;

    public function __construct(
        string $message,
        int $statusCode = 0,
        ?string $errorCode = null,
        array $responseBody = []
    ) {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->responseBody = $responseBody;
    }
}

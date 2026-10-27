<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when execution of a Roboflow Serverless Workflow fails.
 */
class RoboflowWorkflowException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?array $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}

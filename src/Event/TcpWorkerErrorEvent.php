<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Event;

use Workerman\Connection\ConnectionInterface;

class TcpWorkerErrorEvent extends TcpWorkerEvent
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly int $code,
        private readonly string $errorMessage,
    ) {
        parent::__construct($connection);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}

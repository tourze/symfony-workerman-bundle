<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Event;

use Workerman\Connection\ConnectionInterface;

class TcpWorkerMessageEvent extends TcpWorkerEvent
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $message,
    ) {
        parent::__construct($connection);
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}

<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Event;

use Workerman\Connection\ConnectionInterface;

abstract class TcpWorkerEvent
{
    public function __construct(
        protected readonly ConnectionInterface $connection,
    ) {
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}

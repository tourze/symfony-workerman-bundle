<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Event;

use Workerman\Worker;

readonly class TcpWorkerStopEvent
{
    public function __construct(
        private Worker $worker,
    ) {
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }
}

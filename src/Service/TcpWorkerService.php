<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerCloseEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerConnectEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerErrorEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerMessageEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStartEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStopEvent;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

class TcpWorkerService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createWorker(string $host, int $port): Worker
    {
        $worker = new Worker("tcp://{$host}:{$port}");
        $worker->count = 1; // 固定为单进程
        $worker->name = 'symfony-tcp-worker';

        $this->configureWorkerCallbacks($worker);

        return $worker;
    }

    private function configureWorkerCallbacks(Worker $worker): void
    {
        $worker->onWorkerStart = function (Worker $worker): void {
            $event = new TcpWorkerStartEvent($worker);
            $this->eventDispatcher->dispatch($event);
        };

        $worker->onWorkerStop = function (Worker $worker): void {
            $event = new TcpWorkerStopEvent($worker);
            $this->eventDispatcher->dispatch($event);
        };

        $worker->onConnect = function (ConnectionInterface $connection): void {
            $event = new TcpWorkerConnectEvent($connection);
            $this->eventDispatcher->dispatch($event);
        };

        $worker->onMessage = function (ConnectionInterface $connection, string $buffer): void {
            $event = new TcpWorkerMessageEvent($connection, $buffer);
            $this->eventDispatcher->dispatch($event);
        };

        $worker->onClose = function (ConnectionInterface $connection): void {
            $event = new TcpWorkerCloseEvent($connection);
            $this->eventDispatcher->dispatch($event);
        };

        $worker->onError = function (ConnectionInterface $connection, int $code, string $msg): void {
            $event = new TcpWorkerErrorEvent($connection, $code, $msg);
            $this->eventDispatcher->dispatch($event);
        };
    }
}

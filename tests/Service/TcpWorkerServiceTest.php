<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerCloseEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerConnectEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerErrorEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerMessageEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStartEvent;
use Tourze\Symfony\WorkermanBundle\Service\TcpWorkerService;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

/**
 * @internal
 */
#[CoversClass(TcpWorkerService::class)]
final class TcpWorkerServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;

    private TcpWorkerService $service;

    public function testCreateWorker(): void
    {
        $host = '127.0.0.1';
        $port = 8080;

        $worker = $this->service->createWorker($host, $port);

        $this->assertInstanceOf(Worker::class, $worker);
        $this->assertSame(1, $worker->count);
        $this->assertSame('symfony-tcp-worker', $worker->name);
        $this->assertSame("tcp://{$host}:{$port}", $worker->getSocketName());
    }

    public function testWorkerCallbacksAreConfigured(): void
    {
        $worker = $this->service->createWorker('127.0.0.1', 8080);

        $this->assertIsCallable($worker->onWorkerStart);
        $this->assertIsCallable($worker->onWorkerStop);
        $this->assertIsCallable($worker->onConnect);
        $this->assertIsCallable($worker->onMessage);
        $this->assertIsCallable($worker->onClose);
        $this->assertIsCallable($worker->onError);
    }

    public function testOnWorkerStartDispatchesEvent(): void
    {
        $worker = $this->service->createWorker('127.0.0.1', 8080);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TcpWorkerStartEvent::class))
        ;

        $onWorkerStart = $worker->onWorkerStart;
        $this->assertIsCallable($onWorkerStart);
        $onWorkerStart($worker);
    }

    public function testOnConnectDispatchesEvent(): void
    {
        $worker = $this->service->createWorker('127.0.0.1', 8080);
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TcpWorkerConnectEvent::class))
        ;

        $onConnect = $worker->onConnect;
        $this->assertIsCallable($onConnect);
        $onConnect($connection);
    }

    public function testOnMessageDispatchesEvent(): void
    {
        $worker = $this->service->createWorker('127.0.0.1', 8080);
        $connection = $this->createMock(ConnectionInterface::class);
        $message = 'test message';

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(function ($event) use ($message) {
                return $event instanceof TcpWorkerMessageEvent
                    && $event->getMessage() === $message;
            }))
        ;

        $onMessage = $worker->onMessage;
        $this->assertIsCallable($onMessage);
        $onMessage($connection, $message);
    }

    public function testOnCloseDispatchesEvent(): void
    {
        $worker = $this->service->createWorker('127.0.0.1', 8080);
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TcpWorkerCloseEvent::class))
        ;

        $onClose = $worker->onClose;
        $this->assertIsCallable($onClose);
        $onClose($connection);
    }

    public function testOnErrorDispatchesEvent(): void
    {
        $worker = $this->service->createWorker('127.0.0.1', 8080);
        $connection = $this->createMock(ConnectionInterface::class);
        $code = 500;
        $msg = 'error message';

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(function ($event) use ($code, $msg) {
                return $event instanceof TcpWorkerErrorEvent
                    && $event->getCode() === $code
                    && $event->getErrorMessage() === $msg;
            }))
        ;

        $onError = $worker->onError;
        $this->assertIsCallable($onError);
        $onError($connection, $code, $msg);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new TcpWorkerService($this->eventDispatcher);
    }
}

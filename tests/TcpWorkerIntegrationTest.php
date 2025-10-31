<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tourze\Symfony\WorkermanBundle\Command\TcpWorkerCommand;
use Tourze\Symfony\WorkermanBundle\Configuration\TcpWorkerConfiguration;
use Tourze\Symfony\WorkermanBundle\Service\TcpWorkerService;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * 集成测试：验证 TCP Worker 服务和事件系统的完整工作流程.
 *
 * @internal
 */
#[CoversClass(TcpWorkerService::class)]
final class TcpWorkerIntegrationTest extends TestCase
{
    private ContainerBuilder $container;

    private EventDispatcher $eventDispatcher;

    private TestEventSubscriber $eventSubscriber;

    public function testCompleteEventFlow(): void
    {
        $service = $this->container->get(TcpWorkerService::class);
        $this->assertInstanceOf(TcpWorkerService::class, $service);
        $worker = $service->createWorker('127.0.0.1', 12345);

        // 模拟 Worker 启动
        if (null !== $worker->onWorkerStart) {
            call_user_func($worker->onWorkerStart, $worker);
        }

        // 创建模拟连接
        // 使用 Mock 对象来模拟 TcpConnection，避免依赖真实的网络连接
        // 这样可以在不启动真实 TCP 服务的情况下测试事件流程
        $connection = $this->createMock(TcpConnection::class);
        $connection->method('getRemoteIp')->willReturn('127.0.0.1');
        $connection->method('getRemotePort')->willReturn(54321);

        // 模拟连接事件
        if (null !== $worker->onConnect) {
            call_user_func($worker->onConnect, $connection);
        }

        // 模拟消息事件
        $testMessage = "Hello Server\n";
        if (null !== $worker->onMessage) {
            call_user_func($worker->onMessage, $connection, $testMessage);
        }

        // 模拟关闭事件
        if (null !== $worker->onClose) {
            call_user_func($worker->onClose, $connection);
        }

        // 模拟 Worker 停止
        if (null !== $worker->onWorkerStop) {
            call_user_func($worker->onWorkerStop, $worker);
        }

        // 验证事件流程
        $this->assertCount(1, $this->eventSubscriber->startEvents);
        $this->assertCount(1, $this->eventSubscriber->connectEvents);
        $this->assertCount(1, $this->eventSubscriber->messageEvents);
        $this->assertCount(1, $this->eventSubscriber->closeEvents);
        $this->assertCount(1, $this->eventSubscriber->stopEvents);

        // 验证消息内容
        $messageEvent = $this->eventSubscriber->messageEvents[0];
        $this->assertEquals($testMessage, $messageEvent->getMessage());
    }

    public function testEventDispatchOrder(): void
    {
        $service = $this->container->get(TcpWorkerService::class);
        $this->assertInstanceOf(TcpWorkerService::class, $service);
        $worker = $service->createWorker('127.0.0.1', 12346);

        // 模拟 Worker 启动
        if (null !== $worker->onWorkerStart) {
            call_user_func($worker->onWorkerStart, $worker);
        }

        // 验证启动事件
        $this->assertCount(1, $this->eventSubscriber->startEvents);
        $this->assertInstanceOf(Worker::class, $this->eventSubscriber->startEvents[0]->getWorker());

        // 创建模拟连接
        // 使用 Mock 对象来模拟 TcpConnection，避免依赖真实的网络连接
        // 这样可以在不启动真实 TCP 服务的情况下测试事件流程
        $connection = $this->createMock(TcpConnection::class);
        $connection->method('getRemoteIp')->willReturn('127.0.0.1');
        $connection->method('getRemotePort')->willReturn(54321);

        // 模拟连接事件
        if (null !== $worker->onConnect) {
            call_user_func($worker->onConnect, $connection);
        }

        // 验证连接事件
        $this->assertCount(1, $this->eventSubscriber->connectEvents);
        $this->assertSame($connection, $this->eventSubscriber->connectEvents[0]->getConnection());

        // 模拟消息事件
        if (null !== $worker->onMessage) {
            call_user_func($worker->onMessage, $connection, 'test message');
        }

        // 验证消息事件
        $this->assertCount(1, $this->eventSubscriber->messageEvents);
        $this->assertEquals('test message', $this->eventSubscriber->messageEvents[0]->getMessage());

        // 模拟错误事件
        if (null !== $worker->onError) {
            call_user_func($worker->onError, $connection, 100, 'test error');
        }

        // 验证错误事件
        $this->assertCount(1, $this->eventSubscriber->errorEvents);
        $this->assertEquals(100, $this->eventSubscriber->errorEvents[0]->getCode());
        $this->assertEquals('test error', $this->eventSubscriber->errorEvents[0]->getErrorMessage());

        // 模拟关闭事件
        if (null !== $worker->onClose) {
            call_user_func($worker->onClose, $connection);
        }

        // 验证关闭事件
        $this->assertCount(1, $this->eventSubscriber->closeEvents);
        $this->assertSame($connection, $this->eventSubscriber->closeEvents[0]->getConnection());

        // 模拟 Worker 停止
        if (null !== $worker->onWorkerStop) {
            call_user_func($worker->onWorkerStop, $worker);
        }

        // 验证停止事件
        $this->assertCount(1, $this->eventSubscriber->stopEvents);
        $this->assertInstanceOf(Worker::class, $this->eventSubscriber->stopEvents[0]->getWorker());
    }

    public function testMultipleConnections(): void
    {
        $service = $this->container->get(TcpWorkerService::class);
        $this->assertInstanceOf(TcpWorkerService::class, $service);
        $worker = $service->createWorker('127.0.0.1', 12347);

        // 创建并连接多个客户端
        $connections = $this->createMultipleConnections($worker, 3);

        // 验证连接数量
        $this->assertCount(3, $this->eventSubscriber->connectEvents);

        // 发送消息到所有连接
        $this->sendMessagesToConnections($worker, $connections);

        // 验证消息
        $this->assertCount(3, $this->eventSubscriber->messageEvents);
        $this->assertMessageContents();

        // 关闭所有连接
        $this->closeAllConnections($worker, $connections);

        // 验证关闭事件数量
        $this->assertCount(3, $this->eventSubscriber->closeEvents);
    }

    /**
     * 创建多个模拟连接.
     *
     * @return array<int, TcpConnection>
     */
    private function createMultipleConnections(Worker $worker, int $count): array
    {
        $connections = [];
        for ($i = 0; $i < $count; ++$i) {
            // 为每个连接创建独立的 Mock 对象，模拟不同的客户端连接
            $connection = $this->createMock(TcpConnection::class);
            $connection->method('getRemoteIp')->willReturn('127.0.0.' . ($i + 1));
            $connection->method('getRemotePort')->willReturn(50000 + $i);
            $connections[] = $connection;

            if (null !== $worker->onConnect) {
                call_user_func($worker->onConnect, $connection);
            }
        }

        return $connections;
    }

    /**
     * 向所有连接发送消息.
     *
     * @param array<int, TcpConnection> $connections
     */
    private function sendMessagesToConnections(Worker $worker, array $connections): void
    {
        foreach ($connections as $i => $connection) {
            if (null !== $worker->onMessage) {
                call_user_func($worker->onMessage, $connection, "Message {$i}");
            }
        }
    }

    /**
     * 验证消息内容.
     */
    private function assertMessageContents(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals("Message {$i}", $this->eventSubscriber->messageEvents[$i]->getMessage());
        }
    }

    /**
     * 关闭所有连接.
     *
     * @param array<int, TcpConnection> $connections
     */
    private function closeAllConnections(Worker $worker, array $connections): void
    {
        foreach ($connections as $connection) {
            if (null !== $worker->onClose) {
                call_user_func($worker->onClose, $connection);
            }
        }
    }

    public function testWorkerConfiguration(): void
    {
        $service = $this->container->get(TcpWorkerService::class);
        $this->assertInstanceOf(TcpWorkerService::class, $service);
        $worker = $service->createWorker('0.0.0.0', 8080);

        // 验证 Worker 配置
        $this->assertEquals(1, $worker->count);
        $this->assertEquals('symfony-tcp-worker', $worker->name);
        $this->assertEquals('tcp://0.0.0.0:8080', $worker->getSocketName());

        // 验证所有回调都已设置
        $this->assertIsCallable($worker->onWorkerStart);
        $this->assertIsCallable($worker->onWorkerStop);
        $this->assertIsCallable($worker->onConnect);
        $this->assertIsCallable($worker->onMessage);
        $this->assertIsCallable($worker->onClose);
        $this->assertIsCallable($worker->onError);
    }

    public function testCreateWorker(): void
    {
        $service = $this->container->get(TcpWorkerService::class);
        $this->assertInstanceOf(TcpWorkerService::class, $service);

        // 测试 createWorker 方法
        $worker = $service->createWorker('192.168.1.1', 9999);

        // 验证返回的是 Worker 实例
        $this->assertInstanceOf(Worker::class, $worker);

        // 验证 socket 地址
        $this->assertEquals('tcp://192.168.1.1:9999', $worker->getSocketName());

        // 验证配置被正确应用
        $this->assertEquals(1, $worker->count);
        $this->assertEquals('symfony-tcp-worker', $worker->name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 设置 Workerman 运行模式为测试模式
        Worker::$daemonize = false;
        Worker::$pidFile = '';
        Worker::$logFile = '/dev/null';
        Worker::$stdoutFile = '/dev/null';

        // 创建容器和事件分发器
        $this->container = new ContainerBuilder();
        $this->eventDispatcher = new EventDispatcher();
        $this->eventSubscriber = new TestEventSubscriber();

        // 注册事件订阅者
        $this->eventDispatcher->addSubscriber($this->eventSubscriber);

        // 注册服务
        $this->container->register(TcpWorkerConfiguration::class, TcpWorkerConfiguration::class)
            ->addArgument('127.0.0.1')
            ->addArgument(12345) // 使用测试端口
            ->addArgument('test-tcp-worker')
            ->setPublic(true)
        ;

        $this->container->register(TcpWorkerService::class, TcpWorkerService::class)
            ->addArgument($this->eventDispatcher)
            ->addArgument(new Reference(TcpWorkerConfiguration::class))
            ->setPublic(true)
        ;

        $this->container->register(TcpWorkerCommand::class, TcpWorkerCommand::class)
            ->addArgument(new Reference(TcpWorkerService::class))
            ->addTag('console.command')
            ->setPublic(true)
        ;

        $this->container->compile();
    }

    protected function tearDown(): void
    {
        // 清理事件记录
        $this->eventSubscriber->reset();
    }
}

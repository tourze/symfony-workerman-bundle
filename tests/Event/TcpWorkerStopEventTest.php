<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStopEvent;
use Workerman\Worker;

/**
 * @internal
 */
#[CoversClass(TcpWorkerStopEvent::class)]
final class TcpWorkerStopEventTest extends TestCase
{
    public function testGetWorker(): void
    {
        /*
         * 使用 Worker 具体类创建模拟对象的原因：
         * 1. Worker 是 Workerman 框架的核心类，没有提供接口
         * 2. 在单元测试中我们只需要模拟 Worker 的行为，不需要真实的网络功能
         * 3. 这是测试事件类的标准做法，只需验证事件能正确存储和返回 Worker 实例
         */
        $worker = $this->createMock(Worker::class);
        $event = new TcpWorkerStopEvent($worker);

        $this->assertSame($worker, $event->getWorker());
    }
}

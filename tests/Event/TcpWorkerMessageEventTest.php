<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerMessageEvent;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(TcpWorkerMessageEvent::class)]
final class TcpWorkerMessageEventTest extends TestCase
{
    public function testGetMessage(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $message = 'Test message';

        $event = new TcpWorkerMessageEvent($connection, $message);

        $this->assertSame($connection, $event->getConnection());
        $this->assertSame($message, $event->getMessage());
    }
}

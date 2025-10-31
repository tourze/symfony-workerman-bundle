<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerConnectEvent;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(TcpWorkerConnectEvent::class)]
final class TcpWorkerConnectEventTest extends TestCase
{
    public function testEventInheritsFromBase(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $event = new TcpWorkerConnectEvent($connection);

        $this->assertSame($connection, $event->getConnection());
    }
}

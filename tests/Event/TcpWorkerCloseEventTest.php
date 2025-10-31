<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerCloseEvent;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(TcpWorkerCloseEvent::class)]
final class TcpWorkerCloseEventTest extends TestCase
{
    public function testEventInheritsFromBase(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $event = new TcpWorkerCloseEvent($connection);

        $this->assertSame($connection, $event->getConnection());
    }
}

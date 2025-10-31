<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerEvent;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(TcpWorkerEvent::class)]
final class TcpWorkerEventTest extends TestCase
{
    public function testGetConnection(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $event = new class($connection) extends TcpWorkerEvent {};

        $this->assertSame($connection, $event->getConnection());
    }
}

<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerErrorEvent;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(TcpWorkerErrorEvent::class)]
final class TcpWorkerErrorEventTest extends TestCase
{
    public function testGetCodeAndMessage(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $code = 500;
        $message = 'Test error';

        $event = new TcpWorkerErrorEvent($connection, $code, $message);

        $this->assertSame($connection, $event->getConnection());
        $this->assertSame($code, $event->getCode());
        $this->assertSame($message, $event->getErrorMessage());
    }
}

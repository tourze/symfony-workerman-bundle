<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerCloseEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerConnectEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerErrorEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerMessageEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStartEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStopEvent;

/**
 * 测试事件订阅者，用于记录事件.
 *
 * @internal
 */
class TestEventSubscriber implements EventSubscriberInterface
{
    /** @var array<int, TcpWorkerStartEvent> */
    public array $startEvents = [];

    /** @var array<int, TcpWorkerStopEvent> */
    public array $stopEvents = [];

    /** @var array<int, TcpWorkerConnectEvent> */
    public array $connectEvents = [];

    /** @var array<int, TcpWorkerMessageEvent> */
    public array $messageEvents = [];

    /** @var array<int, TcpWorkerCloseEvent> */
    public array $closeEvents = [];

    /** @var array<int, TcpWorkerErrorEvent> */
    public array $errorEvents = [];

    public static function getSubscribedEvents(): array
    {
        return [
            TcpWorkerStartEvent::class => 'onStart',
            TcpWorkerStopEvent::class => 'onStop',
            TcpWorkerConnectEvent::class => 'onConnect',
            TcpWorkerMessageEvent::class => 'onMessage',
            TcpWorkerCloseEvent::class => 'onClose',
            TcpWorkerErrorEvent::class => 'onError',
        ];
    }

    public function onStart(TcpWorkerStartEvent $event): void
    {
        $this->startEvents[] = $event;
    }

    public function onStop(TcpWorkerStopEvent $event): void
    {
        $this->stopEvents[] = $event;
    }

    public function onConnect(TcpWorkerConnectEvent $event): void
    {
        $this->connectEvents[] = $event;
    }

    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $this->messageEvents[] = $event;
    }

    public function onClose(TcpWorkerCloseEvent $event): void
    {
        $this->closeEvents[] = $event;
    }

    public function onError(TcpWorkerErrorEvent $event): void
    {
        $this->errorEvents[] = $event;
    }

    public function reset(): void
    {
        $this->startEvents = [];
        $this->stopEvents = [];
        $this->connectEvents = [];
        $this->messageEvents = [];
        $this->closeEvents = [];
        $this->errorEvents = [];
    }
}

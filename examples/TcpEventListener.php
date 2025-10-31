<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerCloseEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerConnectEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerErrorEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerMessageEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStartEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerStopEvent;

/**
 * Example TCP Event Listener
 *
 * This example demonstrates how to handle TCP Worker events in your Symfony application.
 * Copy this file to your src/EventListener directory and it will be automatically registered.
 */
class TcpEventListener implements EventSubscriberInterface
{
    private array $connections = [];

    public static function getSubscribedEvents(): array
    {
        return [
            TcpWorkerStartEvent::class => 'onWorkerStart',
            TcpWorkerStopEvent::class => 'onWorkerStop',
            TcpWorkerConnectEvent::class => 'onConnect',
            TcpWorkerMessageEvent::class => 'onMessage',
            TcpWorkerCloseEvent::class => 'onClose',
            TcpWorkerErrorEvent::class => 'onError',
        ];
    }

    public function onWorkerStart(TcpWorkerStartEvent $event): void
    {
        $worker = $event->getWorker();
        echo "[Worker] TCP Worker started on {$worker->getSocketName()}\n";
    }

    public function onWorkerStop(TcpWorkerStopEvent $event): void
    {
        echo "[Worker] TCP Worker stopped\n";
    }

    public function onConnect(TcpWorkerConnectEvent $event): void
    {
        $connection = $event->getConnection();

        // Assign a unique ID to the connection
        $connection->uid = uniqid('client_');
        $this->connections[$connection->uid] = $connection;

        echo "[Connect] Client {$connection->uid} connected from {$connection->getRemoteIp()}:{$connection->getRemotePort()}\n";

        // Send welcome message
        $connection->send("Welcome! Your ID is {$connection->uid}\n");
        $connection->send("Type 'help' for available commands\n");
    }

    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $connection = $event->getConnection();
        $message = trim($event->getMessage());

        echo "[Message] Client {$connection->uid}: {$message}\n";

        // Handle commands
        switch (strtolower($message)) {
            case 'help':
                $this->sendHelp($connection);
                break;

            case 'time':
                $connection->send('Server time: ' . date('Y-m-d H:i:s') . "\n");
                break;

            case 'list':
                $this->listConnections($connection);
                break;

            case 'quit':
            case 'exit':
                $connection->send("Goodbye!\n");
                $connection->close();
                break;

            default:
                if (0 === strpos($message, 'broadcast ')) {
                    $broadcastMessage = substr($message, 10);
                    $this->broadcast($connection, $broadcastMessage);
                } else {
                    // Echo the message back
                    $connection->send("Echo: {$message}\n");
                }
        }
    }

    private function sendHelp($connection): void
    {
        $help = <<<'HELP'
            Available commands:
              help      - Show this help message
              time      - Show server time
              list      - List all connected clients
              broadcast <message> - Send message to all clients
              quit/exit - Disconnect from server
              
            Any other message will be echoed back.
            HELP;

        $connection->send($help . "\n");
    }

    private function listConnections($connection): void
    {
        $count = count($this->connections);
        $connection->send("Connected clients ({$count}):\n");

        foreach ($this->connections as $uid => $conn) {
            $info = sprintf(
                "  - %s from %s:%s\n",
                $uid,
                $conn->getRemoteIp(),
                $conn->getRemotePort()
            );
            $connection->send($info);
        }
    }

    private function broadcast($sender, string $message): void
    {
        $senderUid = $sender->uid ?? 'unknown';
        $broadcastMessage = "[Broadcast from {$senderUid}] {$message}\n";

        foreach ($this->connections as $connection) {
            $connection->send($broadcastMessage);
        }
    }

    public function onClose(TcpWorkerCloseEvent $event): void
    {
        $connection = $event->getConnection();

        if (isset($connection->uid)) {
            echo "[Close] Client {$connection->uid} disconnected\n";
            unset($this->connections[$connection->uid]);

            // Notify other clients
            $this->broadcastSystem("{$connection->uid} has left the server");
        }
    }

    private function broadcastSystem(string $message): void
    {
        $systemMessage = "[System] {$message}\n";

        foreach ($this->connections as $connection) {
            $connection->send($systemMessage);
        }
    }

    public function onError(TcpWorkerErrorEvent $event): void
    {
        $connection = $event->getConnection();
        $code = $event->getCode();
        $message = $event->getErrorMessage();

        echo "[Error] Client {$connection->uid}: Error {$code} - {$message}\n";
    }
}

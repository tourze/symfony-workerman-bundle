# symfony-workerman-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![License](https://img.shields.io/packagist/l/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

Symfony integration bundle for Workerman, a high-performance asynchronous PHP socket framework.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Commands](#commands)
- [Core Concepts](#core-concepts)
- [Advanced Usage](#advanced-usage)
- [Architecture Diagram](#architecture-diagram)
- [Technical Notes](#technical-notes)
- [Dependencies](#dependencies)
- [Contributing](#contributing)
- [License](#license)

## Features

- Command-line based Workerman service management
- Automatic discovery and registration of Worker services through interfaces
- Support for TCP/UDP and other network protocols
- Buffer management mechanism
- Integrated Crontab scheduling system
- Automatic Worker process optimization based on CPU cores
- Seamless integration with Symfony dependency injection container

## Installation

```bash
composer require tourze/symfony-workerman-bundle
```

## Quick Start

### 1. Create a Worker Service

```php
<?php

namespace App\Worker;

use Tourze\Symfony\WorkermanBundle\Contracts\ConnectableInterface;
use Tourze\Symfony\WorkermanBundle\Contracts\WorkerBuilderInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class EchoWorker implements WorkerBuilderInterface, ConnectableInterface
{
    public function getName(): string
    {
        return 'echo-server';
    }

    public function getTransport(): string
    {
        return 'tcp';
    }

    public function getListenIp(): string
    {
        return '0.0.0.0';
    }

    public function getListenPort(): int
    {
        return 2345;
    }

    public function onWorkerStart(Worker $worker): void
    {
        echo "Worker started\n";
    }

    public function onWorkerStop(Worker $worker): void
    {
        echo "Worker stopped\n";
    }

    public function onWorkerReload(Worker $worker): void
    {
        echo "Worker reloaded\n";
    }

    public function onConnect(TcpConnection $connection): void
    {
        echo "New connection from {$connection->getRemoteIp()}\n";
    }

    public function onMessage(TcpConnection $connection, mixed $data): void
    {
        $connection->send("Echo: {$data}");
    }

    public function onClose(TcpConnection $connection): void
    {
        echo "Connection closed\n";
    }

    public function onError(TcpConnection $connection, int $code, string $msg): void
    {
        echo "Error: {$msg} (code: {$code})\n";
    }
}
```

### 2. Create a Timer Task

```php
<?php

namespace App\Timer;

use Tourze\Symfony\WorkermanBundle\Contracts\TimerInterface;

class HeartbeatTimer implements TimerInterface
{
    public function getExpression(): string
    {
        return '*/30 * * * * *'; // Every 30 seconds
    }

    public function execute(): void
    {
        echo "Heartbeat at " . date('Y-m-d H:i:s') . "\n";
    }
}
```

### 3. Run the Workerman Service

```bash
# Start the service
php bin/console workerman:run start

# Start in daemon mode
php bin/console workerman:run start --daemon

# Stop the service
php bin/console workerman:run stop

# Restart the service
php bin/console workerman:run restart

# Reload the service
php bin/console workerman:run reload

# Check service status
php bin/console workerman:run status

# Check connections
php bin/console workerman:run connections
```

## Commands

### workerman:run

The main command to manage Workerman services.

**Usage:**
```bash
php bin/console workerman:run <action> [options]
```

**Arguments:**
- `action`: Required. The action to perform (start|stop|restart|reload|status|connections)

**Options:**
- `-d, --daemon`: Run in daemon mode (for start action)

**Actions:**
- `start`: Start all registered Worker services
- `stop`: Stop all running Worker services
- `restart`: Restart all Worker services
- `reload`: Gracefully reload all Worker services
- `status`: Display the status of all Worker services
- `connections`: Display connection information

### workerman:tcp

Start a single-process TCP Worker server with Symfony Event integration.

**Usage:**
```bash
php bin/console workerman:tcp [options]
```

**Options:**
- `--host`: Listen host (default: 127.0.0.1 or env WORKERMAN_TCP_HOST)
- `-p, --port`: Listen port (default: 2345 or env WORKERMAN_TCP_PORT)
- `-d, --daemon`: Run in daemon mode

**Example:**
```bash
# Start TCP server on default port
php bin/console workerman:tcp

# Start on custom host and port
php bin/console workerman:tcp --host=0.0.0.0 --port=8080

# Start in daemon mode
php bin/console workerman:tcp -d
```

## Core Concepts

### Interfaces

1. **WorkerBuilderInterface**: Base interface for Worker services
    - `getName()`: Define service identifier
    - `onWorkerStart()`: Worker startup initialization
    - `onWorkerStop()`: Worker cleanup logic
    - `onWorkerReload()`: Worker reload handling

2. **ConnectableInterface**: Interface for network-connected Workers
    - `getTransport()`: Specify transport protocol (tcp/udp)
    - `getListenIp()/getListenPort()`: Listen configuration
    - Connection events: `onConnect/onClose/onMessage/onError`

3. **BufferAwareInterface**: Buffer management interface
    - `onBufferFull()`: Handle when send buffer is full
    - `onBufferDrain()`: Handle when send buffer is drained

4. **TimerInterface**: Timer task interface based on workerman/crontab
    - `getExpression()`: Define Cron expression
    - `execute()`: Task execution logic

### Service Auto-configuration

Services implementing the above interfaces are automatically tagged:
- `WorkerBuilderInterface` → `workerman.worker`
- `ConnectableInterface` → `workerman.connectable`
- `BufferAwareInterface` → `workerman.buffer-aware`
- `TimerInterface` → `workerman.timer`

## TCP Worker Event System

The `workerman:tcp` command provides a Symfony Event-based TCP server. All Workerman events are converted to Symfony Events for easy integration.

### Available Events

- `TcpWorkerStartEvent`: Fired when the Worker starts
- `TcpWorkerStopEvent`: Fired when the Worker stops
- `TcpWorkerConnectEvent`: Fired when a client connects
- `TcpWorkerMessageEvent`: Fired when a message is received
- `TcpWorkerCloseEvent`: Fired when a connection closes
- `TcpWorkerErrorEvent`: Fired when an error occurs

### Example Event Listener

```php
<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerConnectEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerMessageEvent;
use Tourze\Symfony\WorkermanBundle\Event\TcpWorkerCloseEvent;

class TcpServerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TcpWorkerConnectEvent::class => 'onConnect',
            TcpWorkerMessageEvent::class => 'onMessage',
            TcpWorkerCloseEvent::class => 'onClose',
        ];
    }
    
    public function onConnect(TcpWorkerConnectEvent $event): void
    {
        $connection = $event->getConnection();
        $connection->send("Welcome to TCP Server!\n");
        
        // Store connection metadata
        $connection->uid = uniqid();
        echo "Client {$connection->uid} connected from {$connection->getRemoteIp()}\n";
    }
    
    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $connection = $event->getConnection();
        $message = $event->getMessage();
        
        // Echo the message back
        $connection->send("Echo: {$message}");
        
        // Broadcast to all connections
        foreach ($connection->worker->connections as $conn) {
            if ($conn->id !== $connection->id) {
                $conn->send("Client {$connection->uid} says: {$message}");
            }
        }
    }
    
    public function onClose(TcpWorkerCloseEvent $event): void
    {
        $connection = $event->getConnection();
        echo "Client {$connection->uid} disconnected\n";
    }
}
```

### Configuration

You can configure the TCP Worker using environment variables:

```bash
# .env
WORKERMAN_TCP_HOST=0.0.0.0
WORKERMAN_TCP_PORT=8080
WORKERMAN_TCP_NAME=my-tcp-server
```

## Advanced Usage

### Custom Protocol Implementation

You can implement custom protocols by extending the protocol handling:

```php
<?php

namespace App\Protocol;

use Workerman\Protocols\ProtocolInterface;

class CustomProtocol implements ProtocolInterface
{
    public static function input($recv_buffer, $connection)
    {
        // Return 0 if not enough data for complete package
        // Return package length if complete package received
        $recv_len = strlen($recv_buffer);
        if ($recv_len < 4) {
            return 0;
        }
        
        $unpack_data = unpack('Nlength', $recv_buffer);
        $package_length = $unpack_data['length'];
        
        if ($recv_len < $package_length + 4) {
            return 0;
        }
        
        return $package_length + 4;
    }
    
    public static function decode($recv_buffer, $connection)
    {
        $data = substr($recv_buffer, 4);
        return json_decode($data, true);
    }
    
    public static function encode($data, $connection)
    {
        $json_data = json_encode($data);
        return pack('N', strlen($json_data)) . $json_data;
    }
}
```

### Advanced Worker Configuration

For complex scenarios, you can customize Worker properties:

```php
<?php

namespace App\Worker;

use Tourze\Symfony\WorkermanBundle\Contracts\WorkerBuilderInterface;
use Workerman\Worker;

class AdvancedWorker implements WorkerBuilderInterface
{
    public function getName(): string
    {
        return 'advanced-worker';
    }
    
    public function onWorkerStart(Worker $worker): void
    {
        // Set custom properties
        $worker->reusePort = true;
        $worker->count = 4; // Override default CPU count
        
        // Initialize resources
        $this->initializeResources();
    }
    
    public function onWorkerStop(Worker $worker): void
    {
        $this->cleanup();
    }
    
    public function onWorkerReload(Worker $worker): void
    {
        $this->reloadConfiguration();
    }
    
    private function initializeResources(): void
    {
        // Initialize databases, caches, etc.
    }
    
    private function cleanup(): void
    {
        // Cleanup resources
    }
    
    private function reloadConfiguration(): void
    {
        // Reload config without restart
    }
}
```

### Environment-specific Configuration

Use Symfony's environment system for different configurations:

```yaml
# config/services.yaml
services:
    App\Worker\MyWorker:
        arguments:
            $port: '%env(WORKER_PORT)%'
            $maxConnections: '%env(int:WORKER_MAX_CONNECTIONS)%'
        tags:
            - { name: 'workerman.worker' }
```

## Architecture Diagram

```text
       RunCommand
          |
          |-- Collects --> WorkerBuilderInterface Services
          |                         |
          |                         |-- Optional --> ConnectableInterface
          |                         |-- Optional --> BufferAwareInterface
          |
          |-- Collects --> TimerInterface Services
          |
          |-- Creates --> Worker Instances
          |                    |
          |                    |-- Binds Events --> WorkerBuilderInterface
          |                    |-- Conditional Events --> ConnectableInterface
          |                    |-- Conditional Events --> BufferAwareInterface
          |                    |-- Registers --> TimerInterface
          |
          |-- Uses --> CpuCoreCounter
```

## Technical Notes

### Requirements
- PHP CLI mode is required
- Use daemon mode in production environments

### Multi-process Considerations
- Be aware of concurrent access to shared resources
- Timer tasks execute in each Worker process
- Services should be stateless or ensure state consistency

### Performance Optimization
- Worker count is automatically set to CPU core count
- For CPU-intensive tasks: keep Workers = CPU cores
- For IO-intensive tasks: can increase Worker count
- Monitor buffer management to prevent memory overflow

## Dependencies

- **Core Dependencies:**
  - `workerman/workerman`: Core async network framework
  - `workerman/crontab`: Timer task support
- **Helper Dependencies:**
  - `fidry/cpu-core-counter`: CPU core detection
  - `phpinnacle/buffer`: Binary data buffer handling

## Contributing

Please follow the standard contribution guidelines for this monorepo.

## License

This package is part of the tourze monorepo and follows its licensing terms.
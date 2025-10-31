# symfony-workerman-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![License](https://img.shields.io/packagist/l/tourze/symfony-workerman-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-workerman-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

Workerman 的 Symfony 集成包，为 Symfony 应用提供高性能异步网络通信能力。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [命令](#命令)
- [核心概念](#核心概念)
- [高级用法](#高级用法)
- [架构图](#架构图)
- [技术说明](#技术说明)
- [依赖](#依赖)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- 基于命令行的 Workerman 服务管理
- 通过接口自动发现和注册 Worker 服务
- 支持 TCP/UDP 等多种网络协议
- 缓冲区管理机制
- 集成 Crontab 定时任务系统
- 基于 CPU 核心数自动优化 Worker 进程数量
- 与 Symfony 依赖注入容器无缝集成

## 安装

```bash
composer require tourze/symfony-workerman-bundle
```

## 快速开始

### 1. 创建 Worker 服务

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
        echo "Worker 已启动\n";
    }

    public function onWorkerStop(Worker $worker): void
    {
        echo "Worker 已停止\n";
    }

    public function onWorkerReload(Worker $worker): void
    {
        echo "Worker 已重载\n";
    }

    public function onConnect(TcpConnection $connection): void
    {
        echo "新连接来自 {$connection->getRemoteIp()}\n";
    }

    public function onMessage(TcpConnection $connection, mixed $data): void
    {
        $connection->send("Echo: {$data}");
    }

    public function onClose(TcpConnection $connection): void
    {
        echo "连接已关闭\n";
    }

    public function onError(TcpConnection $connection, int $code, string $msg): void
    {
        echo "错误: {$msg} (代码: {$code})\n";
    }
}
```

### 2. 创建定时任务

```php
<?php

namespace App\Timer;

use Tourze\Symfony\WorkermanBundle\Contracts\TimerInterface;

class HeartbeatTimer implements TimerInterface
{
    public function getExpression(): string
    {
        return '*/30 * * * * *'; // 每30秒执行一次
    }

    public function execute(): void
    {
        echo "心跳检测 " . date('Y-m-d H:i:s') . "\n";
    }
}
```

### 3. 运行 Workerman 服务

```bash
# 启动服务
php bin/console workerman:run start

# 以守护进程模式启动
php bin/console workerman:run start --daemon

# 停止服务
php bin/console workerman:run stop

# 重启服务
php bin/console workerman:run restart

# 重新加载服务
php bin/console workerman:run reload

# 查看服务状态
php bin/console workerman:run status

# 查看连接信息
php bin/console workerman:run connections
```

## 命令

### workerman:run

管理 Workerman 服务的主要命令。

**用法：**
```bash
php bin/console workerman:run <action> [options]
```

**参数：**
- `action`：必需。要执行的操作 (start|stop|restart|reload|status|connections)

**选项：**
- `-d, --daemon`：以守护进程模式运行（仅用于 start 操作）

**操作说明：**
- `start`：启动所有已注册的 Worker 服务
- `stop`：停止所有运行中的 Worker 服务
- `restart`：重启所有 Worker 服务
- `reload`：平滑重载所有 Worker 服务
- `status`：显示所有 Worker 服务的状态
- `connections`：显示连接信息

### workerman:tcp

启动一个单进程的 TCP Worker 服务器，集成 Symfony 事件系统。

**用法：**
```bash
php bin/console workerman:tcp [options]
```

**选项：**
- `--host`：监听地址（默认：127.0.0.1 或环境变量 WORKERMAN_TCP_HOST）
- `-p, --port`：监听端口（默认：2345 或环境变量 WORKERMAN_TCP_PORT）
- `-d, --daemon`：以守护进程模式运行

**示例：**
```bash
# 在默认端口启动 TCP 服务器
php bin/console workerman:tcp

# 在自定义地址和端口启动
php bin/console workerman:tcp --host=0.0.0.0 --port=8080

# 以守护进程模式启动
php bin/console workerman:tcp -d
```

## 核心概念

### 接口

1. **WorkerBuilderInterface**：Worker 服务的基础接口
    - `getName()`：定义服务标识
    - `onWorkerStart()`：Worker 启动时的初始化逻辑
    - `onWorkerStop()`：Worker 停止时的清理逻辑
    - `onWorkerReload()`：Worker 重载时的处理逻辑

2. **ConnectableInterface**：可连接的 Worker 服务接口
    - `getTransport()`：指定传输协议（tcp/udp）
    - `getListenIp()/getListenPort()`：监听配置
    - 连接事件：`onConnect/onClose/onMessage/onError`

3. **BufferAwareInterface**：缓冲区管理接口
    - `onBufferFull()`：发送缓冲区满时的处理
    - `onBufferDrain()`：发送缓冲区清空时的处理

4. **TimerInterface**：定时任务接口，基于 workerman/crontab
    - `getExpression()`：定义 Cron 表达式
    - `execute()`：定时执行的任务逻辑

### 服务自动配置

实现上述接口的服务会自动被标记：
- `WorkerBuilderInterface` → `workerman.worker`
- `ConnectableInterface` → `workerman.connectable`
- `BufferAwareInterface` → `workerman.buffer-aware`
- `TimerInterface` → `workerman.timer`

## TCP Worker 事件系统

`workerman:tcp` 命令提供了基于 Symfony 事件的 TCP 服务器。所有 Workerman 事件都被转换为 Symfony 事件，便于集成。

### 可用事件

- `TcpWorkerStartEvent`：Worker 启动时触发
- `TcpWorkerStopEvent`：Worker 停止时触发
- `TcpWorkerConnectEvent`：客户端连接时触发
- `TcpWorkerMessageEvent`：收到消息时触发
- `TcpWorkerCloseEvent`：连接关闭时触发
- `TcpWorkerErrorEvent`：发生错误时触发

### 事件监听器示例

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
        $connection->send("欢迎连接到 TCP 服务器！\n");
        
        // 存储连接元数据
        $connection->uid = uniqid();
        echo "客户端 {$connection->uid} 从 {$connection->getRemoteIp()} 连接\n";
    }
    
    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $connection = $event->getConnection();
        $message = $event->getMessage();
        
        // 回显消息
        $connection->send("回显: {$message}");
        
        // 广播给所有连接
        foreach ($connection->worker->connections as $conn) {
            if ($conn->id !== $connection->id) {
                $conn->send("客户端 {$connection->uid} 说: {$message}");
            }
        }
    }
    
    public function onClose(TcpWorkerCloseEvent $event): void
    {
        $connection = $event->getConnection();
        echo "客户端 {$connection->uid} 断开连接\n";
    }
}
```

### 配置

您可以使用环境变量配置 TCP Worker：

```bash
# .env
WORKERMAN_TCP_HOST=0.0.0.0
WORKERMAN_TCP_PORT=8080
WORKERMAN_TCP_NAME=my-tcp-server
```

## 高级用法

### 自定义协议实现

您可以通过扩展协议处理来实现自定义协议：

```php
<?php

namespace App\Protocol;

use Workerman\Protocols\ProtocolInterface;

class CustomProtocol implements ProtocolInterface
{
    public static function input($recv_buffer, $connection)
    {
        // 如果数据不够完整包，返回 0
        // 如果收到完整包，返回包长度
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

### 高级 Worker 配置

对于复杂场景，您可以自定义 Worker 属性：

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
        // 设置自定义属性
        $worker->reusePort = true;
        $worker->count = 4; // 覆盖默认的 CPU 数量
        
        // 初始化资源
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
        // 初始化数据库、缓存等
    }
    
    private function cleanup(): void
    {
        // 清理资源
    }
    
    private function reloadConfiguration(): void
    {
        // 重载配置而不重启
    }
}
```

### 环境特定配置

使用 Symfony 的环境系统进行不同配置：

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

## 架构图

```text
       RunCommand
          |
          |-- 收集 --> WorkerBuilderInterface 服务
          |                         |
          |                         |-- 可选 --> ConnectableInterface
          |                         |-- 可选 --> BufferAwareInterface
          |
          |-- 收集 --> TimerInterface 服务
          |
          |-- 创建 --> Worker 实例
          |                    |
          |                    |-- 绑定事件 --> WorkerBuilderInterface
          |                    |-- 条件事件 --> ConnectableInterface
          |                    |-- 条件事件 --> BufferAwareInterface
          |                    |-- 注册 --> TimerInterface
          |
          |-- 使用 --> CpuCoreCounter
```

## 技术说明

### 要求
- 需要 PHP CLI 模式
- 生产环境建议使用守护进程模式

### 多进程注意事项
- 注意共享资源的并发访问问题
- 定时任务在每个 Worker 进程中都会执行
- 服务应保持无状态或确保状态一致性

### 性能优化
- Worker 数量自动设置为 CPU 核心数
- CPU 密集型任务：保持 Worker 数 = CPU 核心数
- IO 密集型任务：可适当增加 Worker 数量
- 监控缓冲区管理以防止内存溢出

## 依赖

- **核心依赖：**
  - `workerman/workerman`：核心异步网络框架
  - `workerman/crontab`：定时任务支持
- **辅助依赖：**
  - `fidry/cpu-core-counter`：CPU 核心数检测
  - `phpinnacle/buffer`：二进制数据缓冲区处理

## 贡献

请遵循此 monorepo 的标准贡献指南。

## 许可证

此包是 tourze monorepo 的一部分，遵循其许可条款。
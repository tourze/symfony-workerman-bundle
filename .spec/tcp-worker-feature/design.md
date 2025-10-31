# TCP Worker 功能技术设计

## 架构概览

```
┌─────────────────────────────────────────────────────────┐
│                    TcpWorkerCommand                      │
│  (创建并配置单进程 TCP Worker，注册事件监听器)             │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│                 TcpWorkerService                         │
│  (管理 Worker 实例，处理 Workerman 事件并分发 Symfony 事件) │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│                  Symfony Event System                    │
│  (TcpWorkerConnectEvent, TcpWorkerMessageEvent, etc.)   │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│                   Event Listeners                        │
│         (业务模块通过监听事件来扩展功能)                    │
└─────────────────────────────────────────────────────────┘
```

## 核心组件设计

### 1. TcpWorkerCommand

```php
namespace Tourze\Symfony\WorkermanBundle\Command;

#[AsCommand(name: 'workerman:tcp', description: 'Start TCP Worker server')]
class TcpWorkerCommand extends Command
{
    public function __construct(
        private readonly TcpWorkerService $tcpWorkerService,
        private readonly TcpWorkerConfiguration $configuration
    ) {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Listen host', '127.0.0.1');
        $this->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Listen port', '2345');
        $this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode');
    }
}
```

### 2. TcpWorkerService

```php
namespace Tourze\Symfony\WorkermanBundle\Service;

class TcpWorkerService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly KernelInterface $kernel
    ) {}
    
    public function createWorker(string $host, int $port): Worker
    {
        $worker = new Worker("tcp://{$host}:{$port}");
        $worker->count = 1; // 固定为单进程
        $worker->name = 'symfony-tcp-worker';
        
        $this->configureWorkerCallbacks($worker);
        
        return $worker;
    }
    
    private function configureWorkerCallbacks(Worker $worker): void
    {
        $worker->onWorkerStart = function (Worker $worker) {
            $event = new TcpWorkerStartEvent($worker);
            $this->eventDispatcher->dispatch($event);
        };
        
        $worker->onConnect = function (ConnectionInterface $connection) {
            $event = new TcpWorkerConnectEvent($connection);
            $this->eventDispatcher->dispatch($event);
        };
        
        // 其他回调配置...
    }
}
```

### 3. 事件类设计

```php
namespace Tourze\Symfony\WorkermanBundle\Event;

// 基础事件类
abstract class TcpWorkerEvent
{
    public function __construct(
        protected readonly ConnectionInterface $connection
    ) {}
    
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}

// 连接事件
class TcpWorkerConnectEvent extends TcpWorkerEvent {}

// 消息事件
class TcpWorkerMessageEvent extends TcpWorkerEvent
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly string $message
    ) {
        parent::__construct($connection);
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
}

// 关闭事件
class TcpWorkerCloseEvent extends TcpWorkerEvent {}

// 错误事件
class TcpWorkerErrorEvent extends TcpWorkerEvent
{
    public function __construct(
        ConnectionInterface $connection,
        private readonly int $code,
        private readonly string $message
    ) {
        parent::__construct($connection);
    }
}

// Worker 生命周期事件
class TcpWorkerStartEvent
{
    public function __construct(
        private readonly Worker $worker
    ) {}
}

class TcpWorkerStopEvent
{
    public function __construct(
        private readonly Worker $worker
    ) {}
}
```

### 4. 配置类

```php
namespace Tourze\Symfony\WorkermanBundle\Configuration;

class TcpWorkerConfiguration
{
    public function __construct(
        private string $defaultHost = '127.0.0.1',
        private int $defaultPort = 2345,
        private string $workerName = 'symfony-tcp-worker'
    ) {}
    
    // Getters...
}
```

### 5. DI 配置扩展

```php
// 在 WorkermanExtension 中添加配置
public function load(array $configs, ContainerBuilder $container): void
{
    // 现有配置...
    
    // TCP Worker 配置
    $container->register(TcpWorkerService::class)
        ->setAutowired(true)
        ->setAutoconfigured(true);
        
    $container->register(TcpWorkerCommand::class)
        ->setAutowired(true)
        ->setAutoconfigured(true)
        ->addTag('console.command');
        
    $container->register(TcpWorkerConfiguration::class)
        ->setArguments([
            '%workerman.tcp.host%',
            '%workerman.tcp.port%',
            '%workerman.tcp.name%'
        ]);
}
```

## 关键设计决策

### 1. 单进程限制实现
- 在 `TcpWorkerService::createWorker()` 中硬编码 `$worker->count = 1`
- 不提供任何配置选项来修改进程数
- 在文档中明确说明单进程限制的原因

### 2. 事件分发策略
- 使用 Symfony 的标准 EventDispatcher
- 事件在 Worker 回调中同步分发
- 事件监听器应避免执行耗时操作，以免阻塞 Worker

### 3. 连接管理
- 连接对象由 Workerman 管理
- 通过事件传递连接引用给监听器
- 监听器可以直接操作连接对象（发送消息、关闭连接等）

### 4. 错误处理
- Workerman 的错误通过 `TcpWorkerErrorEvent` 传递
- 命令执行错误通过标准的 Console 错误处理
- 提供详细的日志记录

## 扩展点

### 1. 自定义事件监听器示例

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomTcpHandler implements EventSubscriberInterface
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
        $connection->send("Welcome to TCP Server\n");
    }
    
    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $message = $event->getMessage();
        $connection = $event->getConnection();
        
        // 处理消息逻辑
        $response = $this->processMessage($message);
        $connection->send($response);
    }
    
    public function onClose(TcpWorkerCloseEvent $event): void
    {
        // 清理资源
    }
}
```

### 2. 配置示例

```yaml
# config/packages/workerman.yaml
workerman:
    tcp:
        host: '0.0.0.0'
        port: 8080
        name: 'my-tcp-server'
```

## 测试策略

### 1. 单元测试
- 测试 Command 的配置和执行
- 测试 Service 的 Worker 创建和配置
- 测试事件的创建和分发

### 2. 集成测试
- 使用 Mock 的 Worker 和 Connection 对象
- 验证事件监听器的调用
- 测试错误处理流程

### 3. 功能测试
- 创建测试客户端连接到 TCP Server
- 验证消息收发
- 测试连接生命周期

## 性能考虑

1. **事件分发开销**：监控事件分发对性能的影响
2. **内存使用**：单进程模式下需要注意内存泄漏
3. **连接数限制**：根据单进程能力设置合理的连接数上限
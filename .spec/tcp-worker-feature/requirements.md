# TCP Worker 功能需求规范

## 功能概述
在 symfony-workerman-bundle 中添加一个专门的 TCP Worker Command，该命令启动一个单进程的 TCP 服务器，并将 Workerman 的连接事件封装为 Symfony Event，以便其他模块可以通过事件监听器扩展功能。

## 核心需求

### 1. TCP Worker Command
- 创建一个新的 Symfony Console Command：`workerman:tcp`
- 该命令启动一个 TCP Worker 服务器
- **进程数必须固定为 1**（因为多进程会涉及 Kernel 对象的资源共用问题）
- 支持配置监听地址和端口
- 支持 daemon 模式运行

### 2. 事件系统集成
将 Workerman 的原生事件封装为 Symfony Event：
- `onConnect` → `TcpWorkerConnectEvent`
- `onMessage` → `TcpWorkerMessageEvent`
- `onClose` → `TcpWorkerCloseEvent`
- `onError` → `TcpWorkerErrorEvent`
- `onWorkerStart` → `TcpWorkerStartEvent`
- `onWorkerStop` → `TcpWorkerStopEvent`

### 3. 配置管理
- 通过 Symfony 配置系统管理 TCP Worker 的配置
- 支持配置监听地址（默认：127.0.0.1）
- 支持配置监听端口（默认：2345）
- 支持配置 Worker 名称

### 4. 可扩展性
- 其他模块可以通过监听 Symfony Event 来扩展功能
- 提供清晰的事件接口和数据传递机制
- 支持在事件处理器中访问连接对象和消息数据

## 功能边界

### 包含
- TCP Worker 的创建和管理
- Workerman 事件到 Symfony Event 的转换
- 基本的连接生命周期管理
- 事件分发机制

### 不包含
- 具体的业务逻辑实现
- 消息协议的解析（由事件监听器负责）
- 多进程支持（明确限制为单进程）
- WebSocket 或其他协议支持（仅 TCP）

## 使用场景

### 1. 游戏服务器
```php
// 监听连接事件
class GameConnectionListener implements EventSubscriberInterface
{
    public function onConnect(TcpWorkerConnectEvent $event): void
    {
        $connection = $event->getConnection();
        // 处理新玩家连接
    }
    
    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $message = $event->getMessage();
        $connection = $event->getConnection();
        // 处理游戏消息
    }
}
```

### 2. IoT 设备通信
```php
// 监听设备消息
class DeviceMessageListener
{
    public function onMessage(TcpWorkerMessageEvent $event): void
    {
        $deviceData = $event->getMessage();
        // 处理设备上报的数据
    }
}
```

### 3. 实时聊天服务
```php
// 处理聊天消息
class ChatMessageHandler
{
    public function handleMessage(TcpWorkerMessageEvent $event): void
    {
        $message = json_decode($event->getMessage(), true);
        // 广播消息给其他用户
    }
}
```

## 约束条件

1. **单进程限制**：由于 Symfony Kernel 的资源共享问题，必须限制为单进程
2. **性能考虑**：事件分发不应显著影响 TCP 通信性能
3. **兼容性**：必须与现有的 `workerman:run` 命令共存
4. **测试覆盖**：新功能必须有完整的单元测试

## 成功标准

1. 能够通过命令启动 TCP Worker
2. 所有 Workerman 事件都能正确转换为 Symfony Event
3. 其他模块可以通过事件监听器扩展功能
4. 单元测试覆盖率达到 90% 以上
5. 文档完善，包含使用示例
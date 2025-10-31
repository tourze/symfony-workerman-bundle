# TCP Worker 功能实施任务

## 任务概览
实现一个单进程的 TCP Worker Command，将 Workerman 事件封装为 Symfony Event。

## 任务分解

### 阶段 1：基础事件系统（2小时）

#### 任务 1.1：创建事件类 [priority: high]
- [ ] 创建基础事件类 `TcpWorkerEvent`
- [ ] 创建连接事件 `TcpWorkerConnectEvent`
- [ ] 创建消息事件 `TcpWorkerMessageEvent`
- [ ] 创建关闭事件 `TcpWorkerCloseEvent`
- [ ] 创建错误事件 `TcpWorkerErrorEvent`
- [ ] 创建 Worker 生命周期事件（Start/Stop）

**文件位置**：
- `src/Event/TcpWorkerEvent.php`
- `src/Event/TcpWorkerConnectEvent.php`
- `src/Event/TcpWorkerMessageEvent.php`
- `src/Event/TcpWorkerCloseEvent.php`
- `src/Event/TcpWorkerErrorEvent.php`
- `src/Event/TcpWorkerStartEvent.php`
- `src/Event/TcpWorkerStopEvent.php`

#### 任务 1.2：创建事件测试 [priority: high]
- [ ] 编写事件类的单元测试
- [ ] 确保测试覆盖率 > 90%

**文件位置**：
- `tests/Event/TcpWorkerEventTest.php`
- `tests/Event/TcpWorkerConnectEventTest.php`
- 等等...

### 阶段 2：核心服务实现（3小时）

#### 任务 2.1：创建 TcpWorkerService [priority: high]
- [ ] 实现 Worker 创建方法
- [ ] 实现事件回调配置
- [ ] 确保进程数固定为 1
- [ ] 集成 Symfony EventDispatcher

**文件位置**：
- `src/Service/TcpWorkerService.php`

#### 任务 2.2：创建配置类 [priority: medium]
- [ ] 创建 `TcpWorkerConfiguration` 类
- [ ] 支持配置默认主机、端口和名称

**文件位置**：
- `src/Configuration/TcpWorkerConfiguration.php`

#### 任务 2.3：服务测试 [priority: high]
- [ ] 编写 Service 的单元测试
- [ ] 模拟 Worker 和事件分发

**文件位置**：
- `tests/Service/TcpWorkerServiceTest.php`
- `tests/Configuration/TcpWorkerConfigurationTest.php`

### 阶段 3：命令实现（2小时）

#### 任务 3.1：创建 TcpWorkerCommand [priority: high]
- [ ] 实现命令配置（选项和参数）
- [ ] 实现 execute 方法
- [ ] 集成 TcpWorkerService
- [ ] 支持 daemon 模式

**文件位置**：
- `src/Command/TcpWorkerCommand.php`

#### 任务 3.2：命令测试 [priority: high]
- [ ] 编写命令的单元测试
- [ ] 测试各种参数组合

**文件位置**：
- `tests/Command/TcpWorkerCommandTest.php`

### 阶段 4：依赖注入配置（1小时）

#### 任务 4.1：更新 DI 配置 [priority: high]
- [ ] 在 `WorkermanExtension` 中注册新服务
- [ ] 配置服务的自动装配
- [ ] 添加命令标签

**文件位置**：
- `src/DependencyInjection/WorkermanExtension.php`

#### 任务 4.2：更新配置定义 [priority: medium]
- [ ] 添加 TCP Worker 的配置选项
- [ ] 更新 Configuration 类

**文件位置**：
- `src/DependencyInjection/Configuration.php`

### 阶段 5：文档和示例（2小时）

#### 任务 5.1：更新 README [priority: medium]
- [ ] 添加 TCP Worker 功能说明
- [ ] 提供使用示例
- [ ] 说明单进程限制

**文件位置**：
- `README.md`
- `README.zh-CN.md`

#### 任务 5.2：创建示例监听器 [priority: low]
- [ ] 创建一个示例事件监听器
- [ ] 展示如何处理各种事件

**文件位置**：
- `examples/TcpEventListener.php`

### 阶段 6：集成测试（1小时）

#### 任务 6.1：端到端测试 [priority: high]
- [ ] 创建集成测试
- [ ] 测试完整的事件流程
- [ ] 验证事件分发

**文件位置**：
- `tests/Integration/TcpWorkerIntegrationTest.php`

## 执行顺序

1. **基础先行**：先完成事件系统（阶段1）
2. **核心功能**：实现服务和配置（阶段2）
3. **用户接口**：创建命令（阶段3）
4. **集成配置**：更新 DI 配置（阶段4）
5. **文档完善**：编写文档和示例（阶段5）
6. **质量保证**：集成测试（阶段6）

## 验收标准

### 代码质量
- [ ] PHPStan Level 8 无错误
- [ ] 单元测试覆盖率 > 90%
- [ ] 代码符合 PSR-12 标准

### 功能完整
- [ ] 可以启动 TCP Worker
- [ ] 所有事件正确分发
- [ ] 进程数固定为 1
- [ ] 支持自定义监听器

### 文档清晰
- [ ] README 包含使用说明
- [ ] 代码有适当的 PHPDoc
- [ ] 提供完整的示例

## 风险和注意事项

1. **单进程限制**：必须在代码和文档中明确说明
2. **事件性能**：监听器不应执行耗时操作
3. **错误处理**：确保异常不会导致 Worker 崩溃
4. **兼容性**：不影响现有的 `workerman:run` 命令

## 时间估算

- 总时间：约 11 小时
- 建议分 2-3 天完成
- 每个阶段完成后进行代码审查
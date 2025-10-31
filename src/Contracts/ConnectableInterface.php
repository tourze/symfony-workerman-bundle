<?php

namespace Tourze\Symfony\WorkermanBundle\Contracts;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Workerman\Connection\ConnectionInterface;

#[AutoconfigureTag(name: 'workerman.connectable')]
interface ConnectableInterface
{
    /**
     * 协议类型，如 tcp/udp
     */
    public function getTransport(): string;

    /**
     * 监听 IP 地址，通常为 127.0.0.1 或 0.0.0.0
     */
    public function getListenIp(): string;

    /**
     * 监听端口
     */
    public function getListenPort(): int;

    /**
     * 连接建立时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-connect.html
     */
    public function onConnect(ConnectionInterface $connection): void;

    /**
     * 连接关闭时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-close.html
     */
    public function onClose(ConnectionInterface $connection): void;

    /**
     * 接收消息时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-message.html
     */
    public function onMessage(ConnectionInterface $connection, string $buffer): void;

    /**
     * 连接错误时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-error.html
     */
    public function onError(ConnectionInterface $connection, int $code, string $msg): void;
}

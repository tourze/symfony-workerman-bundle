<?php

namespace Tourze\Symfony\WorkermanBundle\Contracts;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Workerman\Connection\ConnectionInterface;

#[AutoconfigureTag(name: 'workerman.buffer-aware')]
interface BufferAwareInterface
{
    /**
     * 缓冲区满时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-buffer-full.html
     */
    public function onBufferFull(ConnectionInterface $connection): void;

    /**
     * 缓冲区清空时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-buffer-drain.html
     */
    public function onBufferDrain(ConnectionInterface $connection): void;
}

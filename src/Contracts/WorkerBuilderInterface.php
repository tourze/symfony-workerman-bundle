<?php

namespace Tourze\Symfony\WorkermanBundle\Contracts;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Workerman\Worker;

#[AutoconfigureTag(name: WorkerBuilderInterface::WORKER_SERVICE_TAG)]
interface WorkerBuilderInterface
{
    public const WORKER_SERVICE_TAG = 'workerman.worker';

    /**
     * 服务名称
     */
    public function getName(): string;

    /**
     * Worker 启动时触发
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-worker-start.html
     */
    public function onWorkerStart(Worker $worker): void;

    /**
     * Worker 停止时触发
     * 官方文档中未提及此方法
     */
    public function onWorkerStop(Worker $worker): void;

    /**
     * Worker 重载时的处理逻辑
     *
     * @see https://manual.workerman.net/doc/zh-cn/worker/on-worker-reload.html
     */
    public function onWorkerReload(Worker $worker): void;
}

<?php

namespace Tourze\Symfony\WorkermanBundle\Command;

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\Symfony\WorkermanBundle\Contracts\BufferAwareInterface;
use Tourze\Symfony\WorkermanBundle\Contracts\ConnectableInterface;
use Tourze\Symfony\WorkermanBundle\Contracts\TimerInterface;
use Tourze\Symfony\WorkermanBundle\Contracts\WorkerBuilderInterface;
use Workerman\Crontab\Crontab;
use Workerman\Worker;

#[AsCommand(name: self::NAME, description: 'Workerman service entry point')]
class RunCommand extends Command
{
    public const NAME = 'workerman:run';

    /**
     * @param iterable<WorkerBuilderInterface> $workerBuilders
     * @param iterable<TimerInterface> $timers
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        #[AutowireIterator(tag: WorkerBuilderInterface::WORKER_SERVICE_TAG)] private readonly iterable $workerBuilders,
        #[AutowireIterator(tag: TimerInterface::SERVICE_TAG)] private readonly iterable $timers,
        private readonly CpuCoreCounter $cpuCoreCounter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'Action to perform (start|stop|restart|reload|status|connections)');
        $this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configureWorkerFiles();
        $this->createAndConfigureWorkers();
        Worker::runAll();

        return Command::SUCCESS;
    }

    private function configureWorkerFiles(): void
    {
        Worker::$pidFile = $this->kernel->getCacheDir() . '/workerman.pid';
        Worker::$logFile = $this->kernel->getCacheDir() . '/workerman.log';
    }

    private function createAndConfigureWorkers(): void
    {
        foreach ($this->workerBuilders as $builder) {
            /** @var WorkerBuilderInterface $builder */
            $worker = $this->createWorker($builder);
            $this->configureWorkerBasics($worker, $builder);
            $this->configureWorkerCallbacks($worker, $builder);
        }
    }

    private function createWorker(WorkerBuilderInterface $builder): Worker
    {
        if ($builder instanceof ConnectableInterface) {
            return new Worker("{$builder->getTransport()}://{$builder->getListenIp()}:{$builder->getListenPort()}");
        }

        return new Worker();
    }

    private function configureWorkerBasics(Worker $worker, WorkerBuilderInterface $builder): void
    {
        $worker->count = $this->cpuCoreCounter->getCount();
        $worker->name = $builder->getName();
        $worker->protocol = null;
    }

    private function configureWorkerCallbacks(Worker $worker, WorkerBuilderInterface $builder): void
    {
        $this->configureBasicCallbacks($worker, $builder);
        $this->configureConnectableCallbacks($worker, $builder);
        $this->configureBufferAwareCallbacks($worker, $builder);
    }

    private function configureBasicCallbacks(Worker $worker, WorkerBuilderInterface $builder): void
    {
        $worker->onWorkerStart = function (Worker $worker) use ($builder): void {
            $this->setupTimers();
            $builder->onWorkerStart($worker);
        };

        $worker->onWorkerStop = $builder->onWorkerStop(...);

        $worker->onWorkerReload = function (Worker $worker) use ($builder): void {
            $this->setupTimers();
            $builder->onWorkerReload($worker);
        };
    }

    private function configureConnectableCallbacks(Worker $worker, WorkerBuilderInterface $builder): void
    {
        if ($builder instanceof ConnectableInterface) {
            $worker->onConnect = $builder->onConnect(...);
            $worker->onClose = $builder->onClose(...);
            $worker->onMessage = $builder->onMessage(...);
            $worker->onError = $builder->onError(...);
        }
    }

    private function configureBufferAwareCallbacks(Worker $worker, WorkerBuilderInterface $builder): void
    {
        if ($builder instanceof BufferAwareInterface) {
            $worker->onBufferFull = $builder->onBufferFull(...);
            $worker->onBufferDrain = $builder->onBufferDrain(...);
        }
    }

    private function setupTimers(): void
    {
        foreach ($this->timers as $timer) {
            assert($timer instanceof TimerInterface);
            new Crontab($timer->getExpression(), $timer->execute(...));
        }
    }
}

<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\Symfony\WorkermanBundle\Configuration\TcpWorkerConfiguration;
use Tourze\Symfony\WorkermanBundle\Service\TcpWorkerService;
use Workerman\Worker;

#[AsCommand(
    name: self::NAME,
    description: 'Start TCP Worker server'
)]
class TcpWorkerCommand extends Command
{
    public const NAME = 'workerman:tcp';

    public function __construct(
        private readonly TcpWorkerService $tcpWorkerService,
        private readonly TcpWorkerConfiguration $configuration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Listen host')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Listen port')
            ->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 获取配置
        $host = (string) ($input->getOption('host') ?? $this->configuration->getDefaultHost());
        $port = (int) ($input->getOption('port') ?? $this->configuration->getDefaultPort());
        $daemon = (bool) $input->getOption('daemon');

        // 设置 daemon 模式
        if ($daemon) {
            Worker::$daemonize = true;
        }

        // 创建 Worker
        $worker = $this->tcpWorkerService->createWorker($host, $port);

        $output->writeln(sprintf(
            '<info>Starting TCP Worker on %s:%d%s</info>',
            $host,
            $port,
            $daemon ? ' (daemon mode)' : ''
        ));

        // 启动 Worker
        Worker::runAll();

        return Command::SUCCESS;
    }
}

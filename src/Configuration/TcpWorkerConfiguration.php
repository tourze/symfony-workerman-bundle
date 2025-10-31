<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Configuration;

class TcpWorkerConfiguration
{
    public function __construct(
        private string $defaultHost = '127.0.0.1',
        private int $defaultPort = 2345,
        private string $workerName = 'symfony-tcp-worker',
    ) {
    }

    public function getDefaultHost(): string
    {
        return $this->defaultHost;
    }

    public function getDefaultPort(): int
    {
        return $this->defaultPort;
    }

    public function getWorkerName(): string
    {
        return $this->workerName;
    }
}

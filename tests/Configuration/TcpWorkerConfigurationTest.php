<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\Configuration\TcpWorkerConfiguration;

/**
 * @internal
 */
#[CoversClass(TcpWorkerConfiguration::class)]
final class TcpWorkerConfigurationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new TcpWorkerConfiguration();

        $this->assertSame('127.0.0.1', $config->getDefaultHost());
        $this->assertSame(2345, $config->getDefaultPort());
        $this->assertSame('symfony-tcp-worker', $config->getWorkerName());
    }

    public function testCustomValues(): void
    {
        $config = new TcpWorkerConfiguration(
            defaultHost: '0.0.0.0',
            defaultPort: 8080,
            workerName: 'custom-worker'
        );

        $this->assertSame('0.0.0.0', $config->getDefaultHost());
        $this->assertSame(8080, $config->getDefaultPort());
        $this->assertSame('custom-worker', $config->getWorkerName());
    }
}

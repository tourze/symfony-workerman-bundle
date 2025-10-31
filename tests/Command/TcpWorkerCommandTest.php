<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\Symfony\WorkermanBundle\Command\TcpWorkerCommand;

/**
 * @internal
 */
#[CoversClass(TcpWorkerCommand::class)]
#[RunTestsInSeparateProcesses]
final class TcpWorkerCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // No special setup required for this test
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(TcpWorkerCommand::class);
        $this->assertInstanceOf(TcpWorkerCommand::class, $command);

        return new CommandTester($command);
    }

    public function testOptionHost(): void
    {
        $command = self::getContainer()->get(TcpWorkerCommand::class);
        $this->assertInstanceOf(TcpWorkerCommand::class, $command);
        $definition = $command->getDefinition();

        // Test that host option exists
        $this->assertTrue($definition->hasOption('host'));
        $hostOption = $definition->getOption('host');
        $this->assertSame('Listen host', $hostOption->getDescription());
        $this->assertTrue($hostOption->acceptValue());
    }

    public function testOptionPort(): void
    {
        $command = self::getContainer()->get(TcpWorkerCommand::class);
        $this->assertInstanceOf(TcpWorkerCommand::class, $command);
        $definition = $command->getDefinition();

        // Test that port option exists
        $this->assertTrue($definition->hasOption('port'));
        $portOption = $definition->getOption('port');
        $this->assertSame('p', $portOption->getShortcut());
        $this->assertSame('Listen port', $portOption->getDescription());
        $this->assertTrue($portOption->acceptValue());
    }

    public function testOptionDaemon(): void
    {
        $command = self::getContainer()->get(TcpWorkerCommand::class);
        $this->assertInstanceOf(TcpWorkerCommand::class, $command);
        $definition = $command->getDefinition();

        // Test that daemon option exists
        $this->assertTrue($definition->hasOption('daemon'));
        $daemonOption = $definition->getOption('daemon');
        $this->assertSame('d', $daemonOption->getShortcut());
        $this->assertFalse($daemonOption->acceptValue());
    }

    public function testCommandIsRegistered(): void
    {
        $command = self::getContainer()->get(TcpWorkerCommand::class);
        $this->assertInstanceOf(TcpWorkerCommand::class, $command);

        $this->assertInstanceOf(TcpWorkerCommand::class, $command);
        $this->assertSame('workerman:tcp', $command->getName());
        $this->assertSame('Start TCP Worker server', $command->getDescription());
    }

    public function testCommandHasRequiredOptions(): void
    {
        $command = self::getContainer()->get(TcpWorkerCommand::class);
        $this->assertInstanceOf(TcpWorkerCommand::class, $command);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertTrue($definition->hasOption('daemon'));

        $hostOption = $definition->getOption('host');
        $this->assertSame('Listen host', $hostOption->getDescription());

        $portOption = $definition->getOption('port');
        $this->assertSame('p', $portOption->getShortcut());
        $this->assertSame('Listen port', $portOption->getDescription());

        $daemonOption = $definition->getOption('daemon');
        $this->assertSame('d', $daemonOption->getShortcut());
        $this->assertFalse($daemonOption->acceptValue());
    }
}

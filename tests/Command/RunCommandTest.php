<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\Symfony\WorkermanBundle\Command\RunCommand;

/**
 * @internal
 */
#[CoversClass(RunCommand::class)]
#[RunTestsInSeparateProcesses]
final class RunCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // No special setup required for this test
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(RunCommand::class);
        $this->assertInstanceOf(RunCommand::class, $command);

        return new CommandTester($command);
    }

    public function testArgumentAction(): void
    {
        $command = self::getContainer()->get(RunCommand::class);
        $this->assertInstanceOf(RunCommand::class, $command);
        $definition = $command->getDefinition();

        // Test that action argument exists and is required
        $this->assertTrue($definition->hasArgument('action'));
        $actionArg = $definition->getArgument('action');
        $this->assertTrue($actionArg->isRequired());
        $this->assertSame('Action to perform (start|stop|restart|reload|status|connections)', $actionArg->getDescription());
    }

    public function testOptionDaemon(): void
    {
        $command = self::getContainer()->get(RunCommand::class);
        $this->assertInstanceOf(RunCommand::class, $command);
        $definition = $command->getDefinition();

        // Test that daemon option exists
        $this->assertTrue($definition->hasOption('daemon'));
        $daemonOption = $definition->getOption('daemon');
        $this->assertSame('d', $daemonOption->getShortcut());
        $this->assertSame('Run in daemon mode', $daemonOption->getDescription());
        $this->assertFalse($daemonOption->acceptValue());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getContainer()->get(RunCommand::class);
        $this->assertInstanceOf(RunCommand::class, $command);

        $this->assertEquals('workerman:run', $command->getName());
        $this->assertEquals('Workerman service entry point', $command->getDescription());
    }

    public function testCommandHasRequiredArguments(): void
    {
        $command = self::getContainer()->get(RunCommand::class);
        $this->assertInstanceOf(RunCommand::class, $command);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('action'));
        $this->assertTrue($definition->getArgument('action')->isRequired());
        $this->assertTrue($definition->hasOption('daemon'));
    }
}

<?php

namespace Tourze\Symfony\WorkermanBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Fidry\CpuCoreCounter\CpuCoreCounter;
use Tourze\Symfony\WorkermanBundle\Command\RunCommand;

class RunCommandTest extends TestCase
{
    public function testCommandConfiguration(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getCacheDir')->willReturn('/tmp');
        
        $cpuCoreCounter = new CpuCoreCounter();
        
        $command = new RunCommand($kernel, [], [], $cpuCoreCounter);
        
        $this->assertEquals('workerman:run', $command->getName());
        $this->assertEquals('Workerman服务入口', $command->getDescription());
    }

    public function testCommandHasRequiredArguments(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $cpuCoreCounter = new CpuCoreCounter();
        
        $command = new RunCommand($kernel, [], [], $cpuCoreCounter);
        
        $application = new Application();
        $application->add($command);
        
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('action'));
        $this->assertTrue($definition->getArgument('action')->isRequired());
        $this->assertTrue($definition->hasOption('daemon'));
    }
}
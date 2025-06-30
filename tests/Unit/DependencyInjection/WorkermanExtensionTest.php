<?php

namespace Tourze\Symfony\WorkermanBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\Symfony\WorkermanBundle\DependencyInjection\WorkermanExtension;

class WorkermanExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $extension = new WorkermanExtension();
        $container = new ContainerBuilder();
        
        $extension->load([], $container);
        
        $this->assertInstanceOf(WorkermanExtension::class, $extension);
    }

    public function testGetAlias(): void
    {
        $extension = new WorkermanExtension();
        
        $this->assertEquals('workerman', $extension->getAlias());
    }
}
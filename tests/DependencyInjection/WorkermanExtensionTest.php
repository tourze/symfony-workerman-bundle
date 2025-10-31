<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\Symfony\WorkermanBundle\DependencyInjection\WorkermanExtension;

/**
 * @internal
 */
#[CoversClass(WorkermanExtension::class)]
final class WorkermanExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private ContainerBuilder $container;

    private WorkermanExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
        $this->extension = new WorkermanExtension();
    }

    public function testExtensionCanBeLoaded(): void
    {
        $configs = [];

        // Test that load method doesn't throw exception
        $this->expectNotToPerformAssertions();
        $this->extension->load($configs, $this->container);
    }

    public function testConfigurationFileExists(): void
    {
        $configFile = __DIR__ . '/../../src/Resources/config/services.yaml';
        $this->assertFileExists($configFile, 'Services configuration file should exist');
    }
}

<?php

declare(strict_types=1);

namespace Tourze\Symfony\WorkermanBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\Symfony\WorkermanBundle\WorkermanBundle;

/**
 * @internal
 */
#[CoversClass(WorkermanBundle::class)]
#[RunTestsInSeparateProcesses]
final class WorkermanBundleTest extends AbstractBundleTestCase
{
}

<?php

namespace Tourze\Symfony\WorkermanBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\WorkermanBundle\WorkermanBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WorkermanBundleTest extends TestCase
{
    public function testBundleInstantiation(): void
    {
        $bundle = new WorkermanBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
        $this->assertInstanceOf(WorkermanBundle::class, $bundle);
    }
}
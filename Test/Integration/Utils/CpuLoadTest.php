<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Utils;

use Magento\TestFramework\Helper\Bootstrap;
use Monolog\Test\TestCase;
use Vendic\OhDear\Utils\CpuLoad as CpuLoadUtils;

class CpuLoadTest extends TestCase
{
    public function testCreateCpuLoadResult() : void
    {
        /** @var CpuLoadUtils $cpuLoadUtils */
        $cpuLoadUtils = Bootstrap::getObjectManager()->get(CpuLoadUtils::class);
        $cpuLoadResult = $cpuLoadUtils->measure();

        // In CI environments, CPU load can be very low, so just check that we get valid numeric values
        $this->assertGreaterThanOrEqual(0.0, $cpuLoadResult->getLoadLastMinute());
        $this->assertGreaterThanOrEqual(0.0, $cpuLoadResult->getLoadLastFiveMinutes());
        $this->assertGreaterThanOrEqual(0.0, $cpuLoadResult->getLoadLastFifteenMinutes());
        
        // Ensure we actually get numeric results
        $this->assertIsFloat($cpuLoadResult->getLoadLastMinute());
        $this->assertIsFloat($cpuLoadResult->getLoadLastFiveMinutes());
        $this->assertIsFloat($cpuLoadResult->getLoadLastFifteenMinutes());
    }
}

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

        $this->assertGreaterThanOrEqual(0.1, $cpuLoadResult->getLoadLastMinute());
        $this->assertGreaterThanOrEqual(0.1, $cpuLoadResult->getLoadLastFiveMinutes());
        $this->assertGreaterThanOrEqual(0.1, $cpuLoadResult->getLoadLastFifteenMinutes());
    }
}

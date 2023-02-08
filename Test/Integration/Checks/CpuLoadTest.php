<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\CpuLoad;
use Vendic\OhDear\Utils\CpuLoad as CpuLoadUtils;
use Vendic\OhDear\Utils\CpuLoad\CpuLoadResults;

/**
 * @magentoAppIsolation enabled
 */
class CpuLoadTest extends TestCase
{

    /**
     * @dataProvider cpuLoadDataProvider
     */
    public function testCpuLoadsAndCheckStatus(
        float $loadLastMinute,
        float $loadLastFiveMinutes,
        float $loadLastFifteenMinutes,
        CheckStatus $expectedStatus,
        string $message = ''
    ): void {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $cpuLoadUtilsMock = $this->getCpuLoadUtilsMock(
            $loadLastMinute,
            $loadLastFiveMinutes,
            $loadLastFifteenMinutes
        );

        $objectManager->addSharedInstance($cpuLoadUtilsMock, CpuLoadUtils::class);

        /** @var CpuLoad $cpuLoadCheck */
        $cpuLoadCheck = $objectManager->get(CpuLoad::class);
        $checkResult = $cpuLoadCheck->run();

        $this->assertEquals('cpu_load', $checkResult->getName());
        $this->assertEquals($expectedStatus, $checkResult->getStatus(), $message);
    }

    public function cpuLoadDataProvider(): array
    {
        return [
            [1, 1, 1, CheckStatus::STATUS_OK, 'All loads are below 1: OK'],
            [20, 1, 1, CheckStatus::STATUS_WARNING, 'Load last minute is 20: warning '],
            [1, 20, 1, CheckStatus::STATUS_WARNING, 'Load last five minutes is 20: warning'],
            [1, 1, 20, CheckStatus::STATUS_WARNING, 'Load last fifteen minutes is 20: warning'],
            [20, 20, 20, CheckStatus::STATUS_WARNING, 'All loads are above 20: warning'],
        ];
    }

    public function testCannotGetCpuLoad(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var CpuLoadUtils & MockObject $cpuLoadUtilsMock */
        $cpuLoadUtilsMock = $this->getMockBuilder(CpuLoadUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['measure'])
            ->getMock();
        $cpuLoadUtilsMock->method('measure')
            ->willThrowException(new LocalizedException(__('Cannot get CPU load')));

        $objectManager->addSharedInstance($cpuLoadUtilsMock, CpuLoadUtils::class);

        /** @var CpuLoad $cpuLoadCheck */
        $cpuLoadCheck = $objectManager->get(CpuLoad::class);
        $checkResult = $cpuLoadCheck->run();

        $this->assertEquals('cpu_load', $checkResult->getName());
        $this->assertEquals('Could not get CPU load', $checkResult->getShortSummary());
        $this->assertEquals(CheckStatus::STATUS_CRASHED, $checkResult->getStatus());
    }

    /**
     * @return CpuLoadUtils&MockObject
     */
    private function getCpuLoadUtilsMock(
        float $loadLastMinute,
        float $loadLastFiveMinutes,
        float $loadLastFifteenMinutes
    ): CpuLoadUtils&MockObject {
        /** @var CpuLoadResults & MockObject $cpuLoadResultMock */
        $cpuLoadResultMock = $this->getMockBuilder(\Vendic\OhDear\Utils\CpuLoad\CpuLoadResults::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoadLastMinute', 'getLoadLastFiveMinutes', 'getLoadLastFifteenMinutes'])
            ->getMock();
        $cpuLoadResultMock->method('getLoadLastMinute')->willReturn($loadLastMinute);
        $cpuLoadResultMock->method('getLoadLastFiveMinutes')->willReturn($loadLastFiveMinutes);
        $cpuLoadResultMock->method('getLoadLastFifteenMinutes')->willReturn($loadLastFifteenMinutes);

        /** @var CpuLoadUtils & MockObject $cpuLoadUtilsMock */
        $cpuLoadUtilsMock = $this->getMockBuilder(CpuLoadUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['measure'])
            ->getMock();
        $cpuLoadUtilsMock->method('measure')->willReturn($cpuLoadResultMock);
        return $cpuLoadUtilsMock;
    }
}

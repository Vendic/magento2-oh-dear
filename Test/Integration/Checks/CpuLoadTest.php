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

    public function testCpuLoadsAndCheckStatus(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        
        // Test data for consecutive calls
        $testCases = [
            ['loads' => [1, 1, 1], 'expectedStatus' => CheckStatus::STATUS_OK, 'message' => 'All loads are below 1: OK'],
            ['loads' => [20, 1, 1], 'expectedStatus' => CheckStatus::STATUS_WARNING, 'message' => 'Load last minute is 20: warning'],
            ['loads' => [1, 20, 1], 'expectedStatus' => CheckStatus::STATUS_WARNING, 'message' => 'Load last five minutes is 20: warning'],
            ['loads' => [1, 1, 20], 'expectedStatus' => CheckStatus::STATUS_WARNING, 'message' => 'Load last fifteen minutes is 20: warning'],
            ['loads' => [20, 20, 20], 'expectedStatus' => CheckStatus::STATUS_WARNING, 'message' => 'All loads are above 20: warning'],
        ];
        
        // Create five separate CpuLoadResults mocks - one for each test case
        /** @var CpuLoadResults & MockObject $result1Mock */
        $result1Mock = $this->createMock(CpuLoadResults::class);
        $result1Mock->method('getLoadLastMinute')->willReturn(1);
        $result1Mock->method('getLoadLastFiveMinutes')->willReturn(1);
        $result1Mock->method('getLoadLastFifteenMinutes')->willReturn(1);
        
        /** @var CpuLoadResults & MockObject $result2Mock */
        $result2Mock = $this->createMock(CpuLoadResults::class);
        $result2Mock->method('getLoadLastMinute')->willReturn(20);
        $result2Mock->method('getLoadLastFiveMinutes')->willReturn(1);
        $result2Mock->method('getLoadLastFifteenMinutes')->willReturn(1);
        
        /** @var CpuLoadResults & MockObject $result3Mock */
        $result3Mock = $this->createMock(CpuLoadResults::class);
        $result3Mock->method('getLoadLastMinute')->willReturn(1);
        $result3Mock->method('getLoadLastFiveMinutes')->willReturn(20);
        $result3Mock->method('getLoadLastFifteenMinutes')->willReturn(1);
        
        /** @var CpuLoadResults & MockObject $result4Mock */
        $result4Mock = $this->createMock(CpuLoadResults::class);
        $result4Mock->method('getLoadLastMinute')->willReturn(1);
        $result4Mock->method('getLoadLastFiveMinutes')->willReturn(1);
        $result4Mock->method('getLoadLastFifteenMinutes')->willReturn(20);
        
        /** @var CpuLoadResults & MockObject $result5Mock */
        $result5Mock = $this->createMock(CpuLoadResults::class);
        $result5Mock->method('getLoadLastMinute')->willReturn(20);
        $result5Mock->method('getLoadLastFiveMinutes')->willReturn(20);
        $result5Mock->method('getLoadLastFifteenMinutes')->willReturn(20);
        
        /** @var CpuLoadUtils & MockObject $cpuLoadUtilsMock */
        $cpuLoadUtilsMock = $this->createMock(CpuLoadUtils::class);
        $cpuLoadUtilsMock->method('measure')
            ->willReturnOnConsecutiveCalls($result1Mock, $result2Mock, $result3Mock, $result4Mock, $result5Mock);

        $objectManager->addSharedInstance($cpuLoadUtilsMock, CpuLoadUtils::class, true);

        // Run each test case
        foreach ($testCases as $index => $testCase) {
            /** @var CpuLoad $cpuLoadCheck */
            $cpuLoadCheck = $objectManager->create(CpuLoad::class);
            $checkResult = $cpuLoadCheck->run();

            $this->assertEquals('cpu_load', $checkResult->getName(), "Test case {$index}: {$testCase['message']}");
            $this->assertEquals($testCase['expectedStatus'], $checkResult->getStatus(), "Test case {$index}: {$testCase['message']}");
        }
    }

    public function testCannotGetCpuLoad(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var CpuLoadUtils & MockObject $cpuLoadUtilsMock */
        $cpuLoadUtilsMock = $this->createMock(CpuLoadUtils::class);
        $cpuLoadUtilsMock->method('measure')
            ->willThrowException(new LocalizedException(__('Cannot get CPU load')));

        $objectManager->addSharedInstance($cpuLoadUtilsMock, CpuLoadUtils::class, true);

        /** @var CpuLoad $cpuLoadCheck */
        $cpuLoadCheck = $objectManager->create(CpuLoad::class);
        $checkResult = $cpuLoadCheck->run();

        $this->assertEquals('cpu_load', $checkResult->getName());
        $this->assertEquals('Could not get CPU load', $checkResult->getShortSummary());
        $this->assertEquals(CheckStatus::STATUS_CRASHED, $checkResult->getStatus());
    }

}

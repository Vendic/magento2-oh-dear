<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\PhpFpmCount;
use Vendic\OhDear\Model\CachedStatusResolver;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Utils\Shell as ShellUtils;

/**
 * @magentoAppIsolation enabled
 */
class PhpFpmCountTest extends TestCase
{
    private const CURR_TIME = 1753797380;

    public function testCheckIsSkippedOnMacOS(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        
        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->createMock(ShellUtils::class);
        $shellUtilsMock->method('isMacOS')->willReturn(true);
        
        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class, true);

        /** @var PhpFpmCount $phpFpmCheck */
        $phpFpmCheck = $objectManager->create(PhpFpmCount::class);
        $checkResult = $phpFpmCheck->run();

        $this->assertEquals('php_fpm_count', $checkResult->getName());
        $this->assertEquals(CheckStatus::STATUS_SKIPPED, $checkResult->getStatus());
    }

    public function testCheckCrashed() : void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->createMock(ShellUtils::class);

        $shellUtilsMock->method('isMacOS')->willReturn(false);
        $shellUtilsMock->method('getPhpFpmProcessCount')
            ->willThrowException(new \Exception('Could not get PHP-FPM process count'));
        
        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class, true);

        /** @var PhpFpmCount $phpFpmCheck */
        $phpFpmCheck = $objectManager->create(PhpFpmCount::class);
        $checkResult = $phpFpmCheck->run();

        $this->assertEquals('php_fpm_count', $checkResult->getName());
        $this->assertEquals(CheckStatus::STATUS_CRASHED, $checkResult->getStatus());
    }

    public function testCheck(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        // Test cases for consecutive calls
        $testCases = [
            [
                'phpFpmCount' => 1,
                'cachedData' => [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => self::CURR_TIME
                ],
                'expectedStatus' => CheckStatus::STATUS_OK,
                'expectedMessage' => CachedStatusResolver::STATUS_OK
            ],
            [
                'phpFpmCount' => 61,
                'cachedData' => [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => self::CURR_TIME
                ],
                'expectedStatus' => CheckStatus::STATUS_WARNING,
                'expectedMessage' => CachedStatusResolver::STATUS_CHANGE
            ],
            [
                'phpFpmCount' => 76,
                'cachedData' => [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => self::CURR_TIME
                ],
                'expectedStatus' => CheckStatus::STATUS_OK,
                'expectedMessage' => CachedStatusResolver::STATUS_IN_THRESHOLD
            ],
            [
                'phpFpmCount' => 76,
                'cachedData' => [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_WARNING->value,
                    'data' => self::CURR_TIME
                ],
                'expectedStatus' => CheckStatus::STATUS_WARNING,
                'expectedMessage' => CachedStatusResolver::STATUS_IN_THRESHOLD
            ],
            [
                'phpFpmCount' => 76,
                'cachedData' => [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => (string)(self::CURR_TIME - (1 * 24 * 3600))
                ],
                'expectedStatus' => CheckStatus::STATUS_FAILED,
                'expectedMessage' => CachedStatusResolver::STATUS_FAIL
            ],
        ];

        // Create ONE ShellUtils mock with consecutive return values
        // Note: getPhpFpmProcessCount() is called twice per test case (once for logic, once for meta)
        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->createMock(ShellUtils::class);
        $shellUtilsMock->method('isMacOS')->willReturn(false);
        
        $phpFpmCounts = array_column($testCases, 'phpFpmCount');
        $doublePhpFpmCounts = [];
        foreach ($phpFpmCounts as $count) {
            $doublePhpFpmCounts[] = $count; // First call for logic
            $doublePhpFpmCounts[] = $count; // Second call for meta
        }
        
        $shellUtilsMock->method('getPhpFpmProcessCount')
            ->willReturnOnConsecutiveCalls(...$doublePhpFpmCounts);

        // Create ONE CacheService mock with consecutive cached data
        /** @var CacheService & MockObject $cacheServiceMock */
        $cacheServiceMock = $this->getMockBuilder(CacheService::class)
            ->setConstructorArgs([$objectManager->get(CacheInterface::class), $objectManager->get(Json::class)])
            ->onlyMethods(['getDataForCheck'])
            ->getMock();
        $cacheServiceMock->method('getDataForCheck')
            ->willReturnOnConsecutiveCalls(...array_column($testCases, 'cachedData'));

        // Create ONE CachedStatusResolver mock
        /** @var CachedStatusResolver & MockObject $statusResolverMock */
        $statusResolverMock = $this->getMockBuilder(CachedStatusResolver::class)
            ->setConstructorArgs([$cacheServiceMock])
            ->onlyMethods(['getTime', 'getMessagesByStatus'])
            ->getMock();
        $statusResolverMock->method('getTime')->willReturn(self::CURR_TIME);
        $statusResolverMock->method('getMessagesByStatus')->willReturn([
            CachedStatusResolver::STATUS_OK => [
                'summary' => CachedStatusResolver::STATUS_OK,
            ],
            CachedStatusResolver::STATUS_CHANGE => [
                'summary' => CachedStatusResolver::STATUS_CHANGE,
                'notification_message' => CachedStatusResolver::STATUS_CHANGE,
            ],
            CachedStatusResolver::STATUS_IN_THRESHOLD => [
                'summary' => CachedStatusResolver::STATUS_IN_THRESHOLD,
                'notification_message' => CachedStatusResolver::STATUS_IN_THRESHOLD,
            ],
            CachedStatusResolver::STATUS_FAIL => [
                'summary' => CachedStatusResolver::STATUS_FAIL,
                'notification_message' => CachedStatusResolver::STATUS_FAIL,
            ],
        ]);

        // Set up shared instances ONCE
        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class, true);
        $objectManager->addSharedInstance($cacheServiceMock, CacheService::class, true);
        $objectManager->addSharedInstance($statusResolverMock, CachedStatusResolver::class, true);

        // Run each test case - the mocks will return consecutive values
        foreach ($testCases as $index => $testCase) {
            /** @var PhpFpmCount $phpFpmCheck */
            $phpFpmCheck = $objectManager->create(PhpFpmCount::class);
            $checkResult = $phpFpmCheck->run();

            $this->assertEquals('php_fpm_count', $checkResult->getName(), "Test case {$index}");
            $this->assertEquals($testCase['expectedStatus'], $checkResult->getStatus(), "Test case {$index}");
            $this->assertEquals($testCase['expectedMessage'], $checkResult->getShortSummary(), "Test case {$index}");
        }
    }
}

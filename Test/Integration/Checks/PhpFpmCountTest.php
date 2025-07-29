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
        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->getMockBuilder(ShellUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMacOS'])
            ->getMock();
        $shellUtilsMock->method('isMacOS')->willReturn(true);

        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class);

        /** @var PhpFpmCount $phpFpmCheck */
        $phpFpmCheck = $objectManager->get(PhpFpmCount::class);
        $checkResult = $phpFpmCheck->run();

        $this->assertEquals('php_fpm_count', $checkResult->getName());
        $this->assertEquals(CheckStatus::STATUS_SKIPPED, $checkResult->getStatus());
    }

    public function testCheckCrashed() : void
    {
        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->getMockBuilder(ShellUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPhpFpmProcessCount', 'isMacOS'])
            ->getMock();

        $shellUtilsMock->method('isMacOS')->willReturn(false);
        $shellUtilsMock->method('getPhpFpmProcessCount')
            ->willThrowException(new \Exception('Could not get PHP-FPM process count'));

        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class);

        /** @var PhpFpmCount $phpFpmCheck */
        $phpFpmCheck = $objectManager->get(PhpFpmCount::class);
        $checkResult = $phpFpmCheck->run();

        $this->assertEquals('php_fpm_count', $checkResult->getName());
        $this->assertEquals(CheckStatus::STATUS_CRASHED, $checkResult->getStatus());
    }

    /**
     * @dataProvider phpFpmCountDataProvider
     */
    public function testCheck(
        int $phpFpmCount,
        array $cachedData,
        CheckStatus $expectedStatus,
        string $expectedMessage
    ): void {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->getMockBuilder(ShellUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMacOS', 'getPhpFpmProcessCount'])
            ->getMock();
        $shellUtilsMock->method('isMacOS')->willReturn(false);
        $shellUtilsMock->method('getPhpFpmProcessCount')->willReturn($phpFpmCount);

        /** @var CacheService & MockObject $shellUtilsMock */
        $cacheServiceMock = $this->getMockBuilder(CacheService::class)
            ->setConstructorArgs([$objectManager->get(CacheInterface::class), $objectManager->get(Json::class)])
            ->onlyMethods(['getDataForCheck'])
            ->getMock();

        $cacheServiceMock->method('getDataForCheck')->willReturn($cachedData);

        $statusResolverMock = $this->getMockBuilder(CachedStatusResolver::class)
            ->setConstructorArgs([$cacheServiceMock])
            ->onlyMethods(['getMessagesByStatus', 'getTime'])
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

        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class);
        $objectManager->addSharedInstance($cacheServiceMock, CacheService::class);
        $objectManager->addSharedInstance($statusResolverMock, CachedStatusResolver::class);

        /** @var PhpFpmCount $phpFpmCheck */
        $phpFpmCheck = $objectManager->get(PhpFpmCount::class);
        $checkResult = $phpFpmCheck->run();

        $this->assertEquals('php_fpm_count', $checkResult->getName());
        $this->assertEquals($expectedStatus, $checkResult->getStatus());
        $this->assertEquals($expectedMessage, $checkResult->getShortSummary());
    }

    public function phpFpmCountDataProvider(): array
    {
        return [
            [
                1,
                [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => self::CURR_TIME
                ],
                CheckStatus::STATUS_OK,
                CachedStatusResolver::STATUS_OK
            ],
            [
                61,
                [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => self::CURR_TIME
                ],
                CheckStatus::STATUS_WARNING,
                CachedStatusResolver::STATUS_CHANGE
            ],
            [
                76,
                [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => self::CURR_TIME
                ],
                CheckStatus::STATUS_OK,
                CachedStatusResolver::STATUS_IN_THRESHOLD
            ],
            [
                76,
                [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_WARNING->value,
                    'data' => self::CURR_TIME
                ],
                CheckStatus::STATUS_WARNING,
                CachedStatusResolver::STATUS_IN_THRESHOLD
            ],
            [
                76,
                [
                    'status' => CheckStatus::STATUS_FAILED->value,
                    'fallback_status' => CheckStatus::STATUS_OK->value,
                    'data' => (string)(self::CURR_TIME - (1 * 24 * 3600))
                ],
                CheckStatus::STATUS_FAILED,
                CachedStatusResolver::STATUS_FAIL
            ],
        ];
    }
}

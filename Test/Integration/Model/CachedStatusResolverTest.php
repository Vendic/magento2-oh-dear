<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CachedStatusResolver;
use Vendic\OhDear\Service\CacheService;

class CachedStatusResolverTest extends TestCase
{
    private const CURR_TIME = 1753797380;

    private ?CacheService $cacheService = null;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        // Create and store the cache service to ensure consistency
        $this->cacheService = $objectManager->create(CacheService::class);
        $objectManager->addSharedInstance($this->cacheService, CacheService::class, true);

        $statusResolver = $this->getMockBuilder(CachedStatusResolver::class)
            ->setConstructorArgs([
                $this->cacheService,
                [
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
                ]
            ])
            ->onlyMethods(['getTime'])
            ->getMock();

        $statusResolver->expects($this->any())->method('getTime')->willReturn(self::CURR_TIME);

        $objectManager->addSharedInstance($statusResolver, CachedStatusResolver::class, true);

        parent::setUp();
    }

    public function testOkStatus(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $checkResult = $objectManager->create(CheckResultInterface::class)
            ->setName('test_check')
            ->setLabel('Test Check');

        // To check if cached old value is removed.
        $this->cacheService->saveCheckData(
            $checkResult->getName(),
            CheckStatus::STATUS_FAILED->value,
            (string)self::CURR_TIME
        );

        $statusResolver = $objectManager->get(CachedStatusResolver::class);

        $checkResult = $statusResolver->updateCacheCheck(
            $checkResult,
            CheckStatus::STATUS_OK
        );

        $this->assertEquals(CheckStatus::STATUS_OK, $checkResult->getStatus());
        $this->assertEquals(CachedStatusResolver::STATUS_OK, $checkResult->getShortSummary());
        $cachedData = $this->cacheService->getDataForCheck($checkResult->getName());
        $this->assertIsArray($cachedData, 'Cached data should be an array');
        $this->assertEquals(
            CheckStatus::STATUS_OK->value,
            $cachedData['status']
        );
    }

    /**
     * @dataProvider statusFlappingDataProvider
     */
    public function testStatusChange(string $cachedStatus, CheckStatus $currentStatus, string $expectedStatus): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $checkResult = $objectManager->create(CheckResultInterface::class)
            ->setName('test_check')
            ->setLabel('Test Check');

        $this->cacheService->saveCheckData(
            $checkResult->getName(),
            $cachedStatus,
            (string)self::CURR_TIME
        );

        $statusResolver = $objectManager->get(CachedStatusResolver::class);

        $checkResult = $statusResolver->updateCacheCheck(
            $checkResult,
            $currentStatus
        );

        $this->assertEquals($expectedStatus, $checkResult->getStatus()->value);
        $this->assertEquals(CachedStatusResolver::STATUS_CHANGE, $checkResult->getShortSummary());

        $cachedData = $this->cacheService->getDataForCheck($checkResult->getName());
        $this->assertIsArray($cachedData, 'Cached data should be an array');
        $savedStatus = $cachedData['status'];
        $this->assertEquals($currentStatus->value, $savedStatus);
    }

    public function testStatusInThreshold(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $checkResult = $objectManager->create(CheckResultInterface::class)
            ->setName('test_check')
            ->setLabel('Test Check');

        // 4 minutes ago
        $referenceCacheTime = (string)(self::CURR_TIME - 4 * 60);

        $this->cacheService->removeCheckData($checkResult->getName(), true);
        $this->cacheService->saveCheckData(
            $checkResult->getName(),
            CheckStatus::STATUS_OK->value,
            (string)($referenceCacheTime - 5)
        );
        $this->cacheService->saveCheckData(
            $checkResult->getName(),
            CheckStatus::STATUS_FAILED->value,
            $referenceCacheTime
        );

        $statusResolver = $objectManager->get(CachedStatusResolver::class);

        // Set threshold to 5 minutes
        $statusResolver->setStatusTimeThreshold(5);

        $checkResult = $statusResolver->updateCacheCheck(
            $checkResult,
            CheckStatus::STATUS_FAILED
        );

        $this->assertEquals(CheckStatus::STATUS_OK, $checkResult->getStatus());
        $this->assertEquals(CachedStatusResolver::STATUS_IN_THRESHOLD, $checkResult->getShortSummary());
        $cachedValue = $this->cacheService->getDataForCheck($checkResult->getName());
        $this->assertIsArray($cachedValue, 'Cached data should be an array');
        $this->assertEquals(CheckStatus::STATUS_FAILED->value, $cachedValue['status']);

        $checkResult = $statusResolver->updateCacheCheck(
            $checkResult,
            CheckStatus::STATUS_WARNING
        );

        $this->assertEquals(CheckStatus::STATUS_OK, $checkResult->getStatus());
        $this->assertEquals(CachedStatusResolver::STATUS_CHANGE, $checkResult->getShortSummary());

        $cachedValue = $this->cacheService->getDataForCheck($checkResult->getName());
        $this->assertIsArray($cachedValue, 'Cached data should be an array');
        $this->assertEquals(CheckStatus::STATUS_WARNING->value, $cachedValue['status']);
    }

    /**
     * @dataProvider failStatusesDataProvider
     */
    public function testActualStatusChange(
        CheckStatus $status
    ) {
        $objectManager = Bootstrap::getObjectManager();

        $checkResult = $objectManager->create(CheckResultInterface::class)
            ->setName('test_check')
            ->setLabel('Test Check');

        // 10 minutes ago
        $referenceCacheTime = (string)(self::CURR_TIME - 10 * 60);

        $this->cacheService->saveCheckData(
            $checkResult->getName(),
            $status->value,
            $referenceCacheTime
        );

        $statusResolver = $objectManager->get(CachedStatusResolver::class);

        // Set threshold to 5 minutes
        $statusResolver->setStatusTimeThreshold(5);

        $checkResult = $statusResolver->updateCacheCheck(
            $checkResult,
            $status
        );

        $this->assertEquals($status, $checkResult->getStatus());
        $this->assertEquals(CachedStatusResolver::STATUS_FAIL, $checkResult->getShortSummary());

        $cachedValue = $this->cacheService->getDataForCheck($checkResult->getName());
        $this->assertIsArray($cachedValue, 'Cached data should be an array');
        $this->assertEquals($status->value, $cachedValue['status']);
        $this->assertEquals($referenceCacheTime, $cachedValue['data']);
    }

    public static function statusFlappingDataProvider()
    {
        return [
            [
                CheckStatus::STATUS_OK->value,
                CheckStatus::STATUS_WARNING,
                CheckStatus::STATUS_OK->value,
            ],
            [
                CheckStatus::STATUS_FAILED->value,
                CheckStatus::STATUS_WARNING,
                CheckStatus::STATUS_WARNING->value,
            ],
            [
                CheckStatus::STATUS_WARNING->value,
                CheckStatus::STATUS_FAILED,
                CheckStatus::STATUS_WARNING->value,
            ]
        ];
    }

    public static function failStatusesDataProvider()
    {
        return [
            [CheckStatus::STATUS_FAILED],
            [CheckStatus::STATUS_WARNING],
        ];
    }
}

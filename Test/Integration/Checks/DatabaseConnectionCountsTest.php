<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\Framework\App\CacheInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\DatabaseConnectionCount;
use Vendic\OhDear\Model\CachedStatusResolver;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Utils\Database as DbUtils;

class DatabaseConnectionCountsTest extends TestCase
{
    public function testDatabaseConnectionCountOk(): void
    {
        $this->setupCache(Bootstrap::getObjectManager());

        /** @var DatabaseConnectionCount $databaseConnectionCheck */
        $databaseConnectionCheck = Bootstrap::getObjectManager()->get(DatabaseConnectionCount::class);

        $checkResult = $databaseConnectionCheck->run();
        $this->assertEquals('db_connection_count', $checkResult->getName());
        $this->assertEquals('DB connection count', $checkResult->getLabel());
        $this->assertEquals(CachedStatusResolver::STATUS_OK, $checkResult->getShortSummary());
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider databaseConnectionCountDataProvider
     */
    public function testDatabaseConnectionCountWarning(
        int         $dbConnections,
        array $cachedData,
        CheckStatus $expectedStatus,
        string $expectedMessage
    ): void {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var DbUtils & MockObject $dbUtilsMock */
        $dbUtilsMock = $this->getMockBuilder(DbUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnectionCount'])
            ->getMock();
        $dbUtilsMock->method('getConnectionCount')->willReturn($dbConnections);

        $objectManager->addSharedInstance($dbUtilsMock, DbUtils::class);

        $this->setupCache($objectManager, $cachedData);

        /** @var DatabaseConnectionCount $databaseConnectionCheck */
        $databaseConnectionCheck = Bootstrap::getObjectManager()->get(DatabaseConnectionCount::class);

        $checkResult = $databaseConnectionCheck->run();
        $this->assertEquals('db_connection_count', $checkResult->getName());
        $this->assertEquals('DB connection count', $checkResult->getLabel());
        $this->assertEquals($expectedStatus, $checkResult->getStatus());
        $this->assertEquals($expectedMessage, $checkResult->getShortSummary());
    }

    /**
     * @param ObjectManager $objectManager
     * @param array|null $cachedData
     * @return void
     */
    public function setupCache(ObjectManager $objectManager, array $cachedData = null): void
    {
        /** @var CacheService & MockObject $shellUtilsMock */
        $cacheServiceMock = $this->getMockBuilder(CacheService::class)
            ->setConstructorArgs([$objectManager->get(CacheInterface::class)])
            ->onlyMethods(['getDataForCheck'])
            ->getMock();

        $cacheServiceMock->method('getDataForCheck')->willReturn($cachedData);

        $statusResolverMock = $this->getMockBuilder(CachedStatusResolver::class)
            ->setConstructorArgs([$cacheServiceMock])
            ->onlyMethods(['getMessagesByStatus'])
            ->getMock();

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

        $objectManager->addSharedInstance($cacheServiceMock, CacheService::class);
        $objectManager->addSharedInstance($statusResolverMock, CachedStatusResolver::class);
    }

    public function databaseConnectionCountDataProvider(): array
    {
        return [
            [
                50,
                ['status' => CheckStatus::STATUS_FAILED->value, 'data' => time()],
                CheckStatus::STATUS_OK,
                CachedStatusResolver::STATUS_OK
            ],
            [
                51,
                ['status' => CheckStatus::STATUS_FAILED->value, 'data' => time()],
                CheckStatus::STATUS_OK,
                CachedStatusResolver::STATUS_CHANGE
            ],
            [
                101,
                ['status' => CheckStatus::STATUS_FAILED->value, 'data' => time()],
                CheckStatus::STATUS_OK,
                CachedStatusResolver::STATUS_IN_THRESHOLD
            ],
            [
                101,
                ['status' => CheckStatus::STATUS_FAILED->value, 'data' => (string)(time() - (24 * 3600))],
                CheckStatus::STATUS_FAILED,
                CachedStatusResolver::STATUS_FAIL
            ]
        ];
    }
}

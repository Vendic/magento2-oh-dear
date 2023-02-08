<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\DatabaseConnectionCount;
use Vendic\OhDear\Utils\Database as DbUtils;

class DatabaseConnectionCountsTest extends TestCase
{
    public function testDatabaseConnectionCountOk(): void
    {
        /** @var DatabaseConnectionCount $databaseConnectionCheck */
        $databaseConnectionCheck = Bootstrap::getObjectManager()->get(DatabaseConnectionCount::class);

        $checkResult = $databaseConnectionCheck->run();
        $this->assertEquals('db_connection_count', $checkResult->getName());
        $this->assertEquals('DB connection count', $checkResult->getLabel());
        $this->assertEquals(DatabaseConnectionCount::OK_SUMMARY, $checkResult->getShortSummary());
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider databaseConnectionCountDataProvider
     */
    public function testDatabaseConnectionCountWarning(int $dbConnections, CheckStatus $expectedStatus): void
    {
        /** @var DbUtils & MockObject $dbUtilsMock */
        $dbUtilsMock = $this->getMockBuilder(DbUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnectionCount'])
            ->getMock();
        $dbUtilsMock->method('getConnectionCount')->willReturn($dbConnections);

        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->addSharedInstance($dbUtilsMock, DbUtils::class);

        /** @var DatabaseConnectionCount $databaseConnectionCheck */
        $databaseConnectionCheck = Bootstrap::getObjectManager()->get(DatabaseConnectionCount::class);

        $checkResult = $databaseConnectionCheck->run();
        $this->assertEquals('db_connection_count', $checkResult->getName());
        $this->assertEquals('DB connection count', $checkResult->getLabel());
        $this->assertEquals($expectedStatus, $checkResult->getStatus());
    }

    public function databaseConnectionCountDataProvider(): array
    {
        return [
            [50, CheckStatus::STATUS_OK],
            [51, CheckStatus::STATUS_WARNING],
            [101, CheckStatus::STATUS_FAILED]
        ];
    }
}

<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\Framework\ObjectManager\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\PublicSqlFiles;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Shell as ShellUtils;

class PublicSqlFilesTest extends TestCase
{
    /**
     * @magentoAppIsolation enabled
     * @dataProvider dataProvider
     */
    public function testGetSqlFilesInPublicLocation(array $sqlFilesInPublicRoot, CheckStatus $expecedCheckStatus): void
    {
        /** @var ShellUtils & MockObject $shellMock */
        $shellMock = $this->getMockBuilder(ShellUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSqlFilesInPublicRoot'])
            ->getMock();
        $shellMock->method('getSqlFilesInPublicRoot')->willReturn($sqlFilesInPublicRoot);

        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        
        // Clear the ObjectManager cache to ensure fresh instances
        $objectManager->clearCache();
        
        // Add the mock as a shared instance
        $objectManager->addSharedInstance($shellMock, ShellUtils::class);

        /** @var PublicSqlFiles $publicSqlFilesCheck */
        $publicSqlFilesCheck = $objectManager->get(PublicSqlFiles::class);
        $checkResult = $publicSqlFilesCheck->run();

        $this->assertEquals($expecedCheckStatus, $checkResult->getStatus());
        $this->assertEquals('public_sql_files', $checkResult->getName());
    }

    public static function dataProvider(): array
    {
        return [
            [
                ['test.sql'],
                CheckStatus::STATUS_FAILED
            ],
            [
                ['test.sql', 'test2.sql'],
                CheckStatus::STATUS_FAILED
            ],
            [
                [],
                CheckStatus::STATUS_OK
            ]
        ];
    }
}

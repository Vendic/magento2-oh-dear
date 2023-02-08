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
use Vendic\OhDear\Checks\PhpFpmCount;
use Vendic\OhDear\Utils\Shell as ShellUtils;

/**
 * @magentoAppIsolation enabled
 */
class PhpFpmCountTest extends TestCase
{
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
    public function testCheck(int $phpFpmCount, CheckStatus $expectedStatus): void
    {
        /** @var ShellUtils & MockObject $shellUtilsMock */
        $shellUtilsMock = $this->getMockBuilder(ShellUtils::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isMacOS', 'getPhpFpmProcessCount'])
            ->getMock();
        $shellUtilsMock->method('isMacOS')->willReturn(false);
        $shellUtilsMock->method('getPhpFpmProcessCount')->willReturn($phpFpmCount);

        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->addSharedInstance($shellUtilsMock, ShellUtils::class);

        /** @var PhpFpmCount $phpFpmCheck */
        $phpFpmCheck = $objectManager->get(PhpFpmCount::class);
        $checkResult = $phpFpmCheck->run();

        $this->assertEquals('php_fpm_count', $checkResult->getName());
        $this->assertEquals($expectedStatus, $checkResult->getStatus());
    }

    public function phpFpmCountDataProvider(): array
    {
        return [
            [1, CheckStatus::STATUS_OK],
            [61, CheckStatus::STATUS_WARNING],
            [76, CheckStatus::STATUS_FAILED],
        ];
    }
}

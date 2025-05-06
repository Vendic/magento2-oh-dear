<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\Diskspace as DiskspaceCheck;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Configuration;
use Vendic\OhDear\Utils\Diskspace;

class DiskspaceTest extends TestCase
{
    public function testDiskspaceRunningLow(): void
    {
        /** @var MockObject & Diskspace $diskspaceUtilsMock */
        $diskspaceUtilsMock = $this->getMockBuilder(Diskspace::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFreeDiskspace', 'getTotalDiskSpace'])
            ->getMock();
        $diskspaceUtilsMock->method('getTotalDiskSpace')->willReturn(100);
        $diskspaceUtilsMock->method('getFreeDiskspace')->willReturn(19);

        $objectManager = Bootstrap::getObjectManager();

        /** @var DiskspaceCheck & MockObject $diskspaceTestMock */
        $diskspaceTestMock = $this->getMockBuilder(DiskspaceCheck::class)
            ->setConstructorArgs(
                [
                    $objectManager->get(CheckResultFactory::class),
                    $objectManager->get(Configuration::class),
                    $diskspaceUtilsMock,
                    80
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $checkResult = $diskspaceTestMock->run();
        $this->assertEquals(CheckStatus::STATUS_FAILED, $checkResult->getStatus());
        $this->assertEquals('Disk space is running low', $checkResult->getNotificationMessage());
        $this->assertEquals('Disk space is 81% used', $checkResult->getShortSummary());
    }

    public function testDiskspaceOk(): void
    {
        /** @var MockObject & Diskspace $diskspaceUtilsMock */
        $diskspaceUtilsMock = $this->getMockBuilder(Diskspace::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFreeDiskspace', 'getTotalDiskSpace'])
            ->getMock();
        $diskspaceUtilsMock->method('getTotalDiskSpace')->willReturn(10);
        $diskspaceUtilsMock->method('getFreeDiskspace')->willReturn(9);

        $objectManager = Bootstrap::getObjectManager();

        /** @var DiskspaceCheck & MockObject $diskspaceTestMock */
        $diskspaceTestMock = $this->getMockBuilder(DiskspaceCheck::class)
            ->setConstructorArgs(
                [
                    $objectManager->get(CheckResultFactory::class),
                    $objectManager->get(Configuration::class),
                    $diskspaceUtilsMock,
                    80
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $checkResult = $diskspaceTestMock->run();
        $this->assertEquals(CheckStatus::STATUS_OK, $checkResult->getStatus());
        $this->assertEquals('Disk space is OK', $checkResult->getNotificationMessage());
        $this->assertEquals('Disk space is 10% used', $checkResult->getShortSummary());
    }
}

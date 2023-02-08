<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Utils;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Utils\BashCommands;
use Vendic\OhDear\Utils\Shell;

class ShellTest extends TestCase
{
    private static ?string $testSqlFile = null;

    public function testGetPhpFpmProcesses(): void
    {
        /** @var Shell $shellUtils */
        $shellUtils = Bootstrap::getObjectManager()->get(Shell::class);

        if ($shellUtils->isMacOS()) {
            $this->markTestSkipped('This test is not supported on Mac OS');
        }

        $this->assertGreaterThanOrEqual(0, $shellUtils->getPhpFpmProcessCount());
    }

    public function testGetSqlFilesInPublicLocation(): void
    {
        $this->createFakeSqlFileInTestRoot();

        /** @var Shell $shellUtils */
        $shellUtils = Bootstrap::getObjectManager()->get(Shell::class);
        $this->assertEquals(1, count($shellUtils->getSqlFilesInPublicRoot()));

        $this->createFakeSqlFileInTestRootRollback();

        $this->assertEquals(0, count($shellUtils->getSqlFilesInPublicRoot()));
    }

    private function createFakeSqlFileInTestRoot(): void
    {
        self::$testSqlFile = uniqid('test-sql-file-') . '.sql';
        $fileSystem = Bootstrap::getObjectManager()->get(\Magento\Framework\Filesystem::class);
        $pubFolder = $fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::PUB);
        $pubFolder->writeFile(self::$testSqlFile, 'test');
    }

    public function createFakeSqlFileInTestRootRollback() : void
    {
        if (self::$testSqlFile) {
            $fileSystem = Bootstrap::getObjectManager()->get(\Magento\Framework\Filesystem::class);
            $pubFolder = $fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::PUB);
            $pubFolder->delete(self::$testSqlFile);
        }
    }
}

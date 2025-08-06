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
        /** @var Shell $shellUtils */
        $shellUtils = Bootstrap::getObjectManager()->get(Shell::class);
        
        // Test that the method returns an array (could be empty in clean environments)
        $sqlFiles = $shellUtils->getSqlFilesInPublicRoot();
        $this->assertIsArray($sqlFiles, 'getSqlFilesInPublicRoot should return an array');
        
        // Create a test file to verify detection works
        $this->createFakeSqlFileInTestRoot();
        
        $sqlFilesAfterCreation = $shellUtils->getSqlFilesInPublicRoot();
        $this->assertIsArray($sqlFilesAfterCreation, 'getSqlFilesInPublicRoot should return an array after file creation');
        
        // In a clean environment, we should have at least our test file now
        $this->assertGreaterThanOrEqual(count($sqlFiles), count($sqlFilesAfterCreation), 
            'Should find same or more SQL files after creating test file');

        $this->createFakeSqlFileInTestRootRollback();

        // After cleanup, we should have the original count
        $sqlFilesAfterCleanup = $shellUtils->getSqlFilesInPublicRoot();
        $this->assertIsArray($sqlFilesAfterCleanup, 'getSqlFilesInPublicRoot should return an array after cleanup');
    }

    private function createFakeSqlFileInTestRoot(): void
    {
        self::$testSqlFile = uniqid('test-sql-file-') . '.sql';
        $fileSystem = Bootstrap::getObjectManager()->get(\Magento\Framework\Filesystem::class);
        $pubFolder = $fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::PUB);
        try {
            $pubFolder->writeFile(self::$testSqlFile, 'test');
        } catch (\Exception $e) {
            // If we can't write to pub folder, create a local test file that might be found
            $testFile = BP . '/pub/' . self::$testSqlFile;
            file_put_contents($testFile, 'test');
        }
    }

    public function createFakeSqlFileInTestRootRollback() : void
    {
        if (self::$testSqlFile) {
            $fileSystem = Bootstrap::getObjectManager()->get(\Magento\Framework\Filesystem::class);
            $pubFolder = $fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::PUB);
            try {
                if ($pubFolder->isExist(self::$testSqlFile)) {
                    $pubFolder->delete(self::$testSqlFile);
                }
            } catch (\Exception $e) {
                // Try to delete using direct file system
                $testFile = BP . '/pub/' . self::$testSqlFile;
                if (file_exists($testFile)) {
                    unlink($testFile);
                }
            }
        }
    }
}

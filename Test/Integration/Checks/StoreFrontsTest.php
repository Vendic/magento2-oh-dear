<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\StoreFronts;
use Vendic\OhDear\Service\CacheService;

class StoreFrontsTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        $this->cacheService = Bootstrap::getObjectManager()->get(CacheService::class);
        $this->cacheService->removeCheckData(StoreFronts::CHECK_NAME);
    }

    protected function tearDown(): void
    {
        $this->cacheService->removeCheckData(StoreFronts::CHECK_NAME);
    }

    public function testWarningWhenCronHasNeverRun(): void
    {
        $output = $this->createCheck()->run();

        $this->assertEquals(StoreFronts::CHECK_NAME, $output->getName());
        $this->assertEquals(
            CheckStatus::STATUS_WARNING,
            $output->getStatus(),
            'Check should warn when no store front results are cached'
        );
    }

    public function testOkWhenAllStoreFrontsAreReachable(): void
    {
        $this->seedCache(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => [],
            ]
        );

        $output = $this->createCheck()->run();

        $this->assertEquals(CheckStatus::STATUS_OK, $output->getStatus());
        $this->assertArrayNotHasKey(
            'failed_domains',
            $output->getMeta(),
            'Alive domains should not be reported, and no failed domains key should be present when all is OK'
        );
        $this->assertEquals(3, $output->getMeta()['checked_count']);
    }

    public function testFailedWhenStoreFrontsAreDown(): void
    {
        $failedDomains = [
            'store2.example.com' => 'HTTP 500',
            'store3.example.com' => 'Connection timed out',
        ];
        $this->seedCache(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => $failedDomains,
            ]
        );

        $output = $this->createCheck()->run();

        $this->assertEquals(CheckStatus::STATUS_FAILED, $output->getStatus());
        $this->assertEquals(
            $failedDomains,
            $output->getMeta()['failed_domains'],
            'Meta should list only the failed domains with their failure reason'
        );
        $this->assertStringContainsString('2', $output->getShortSummary());
    }

    public function testWarningWhenResultsAreStale(): void
    {
        $this->seedCache(
            [
                'checked_at' => time() - 8000,
                'checked_count' => 3,
                'failed' => [],
            ]
        );

        $output = $this->createCheck()->run();

        $this->assertEquals(
            CheckStatus::STATUS_WARNING,
            $output->getStatus(),
            'Check should warn when the cached results are older than the max result age'
        );
    }

    private function createCheck(): StoreFronts
    {
        return Bootstrap::getObjectManager()->create(StoreFronts::class);
    }

    private function seedCache(array $data): void
    {
        $this->cacheService->saveCheckData(
            StoreFronts::CHECK_NAME,
            $data['failed'] === [] ? CheckStatus::STATUS_OK->value : CheckStatus::STATUS_FAILED->value,
            $data
        );
    }
}

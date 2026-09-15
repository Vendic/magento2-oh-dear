<?php

declare(strict_types=1);

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
        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
    }

    public function testOkWhenCronHasNeverRun(): void
    {
        $output = $this->createCheck()->run();

        $this->assertEquals(StoreFronts::CHECK_NAME, $output->getName());
        $this->assertEquals(
            CheckStatus::STATUS_OK,
            $output->getStatus(),
            'Check should not alert when no store front results are cached yet, e.g. right after a deploy'
        );
    }

    public function testOkWhenThereAreNoChildStoresToCheck(): void
    {
        $this->seedResults(
            [
                'checked_at' => time(),
                'checked_count' => 0,
                'failed' => [],
            ]
        );

        $output = $this->createCheck()->run();

        $this->assertEquals(
            CheckStatus::STATUS_OK,
            $output->getStatus(),
            'A single-store instance has no children store fronts and should report OK'
        );
    }

    public function testOkWhenAllStoreFrontsAreReachable(): void
    {
        $this->seedResults(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => [],
            ]
        );

        $output = $this->createCheck()->run();

        $this->assertEquals(CheckStatus::STATUS_OK, $output->getStatus());
        $this->assertArrayNotHasKey(
            'failed_urls',
            $output->getMeta(),
            'Alive store fronts should not be reported, and no failed urls key should be present when all is OK'
        );
        $this->assertEquals(3, $output->getMeta()['checked_count']);
    }

    public function testFailedWhenStoreFrontsStayDownBeyondTheStatusTimeThreshold(): void
    {
        $failedUrls = [
            'https://store2.example.com/' => 'HTTP 500',
            'https://ivol.example.com/deurmat24_nl/' => 'Connection timed out',
        ];
        $this->seedResults(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => $failedUrls,
            ]
        );
        // The failed status has been cached longer ago than the status time threshold
        $this->seedResolverState(CheckStatus::STATUS_FAILED->value, time() - 600);

        $output = $this->createCheck(statusTimeThreshold: 0)->run();

        $this->assertEquals(CheckStatus::STATUS_FAILED, $output->getStatus());
        $this->assertEquals(
            $failedUrls,
            $output->getMeta()['failed_urls'],
            'Meta should list only the failed store front URLs with their failure reason'
        );
        $this->assertStringContainsString('2', $output->getNotificationMessage());
    }

    public function testFirstFailureIsDampedByTheStatusTimeThreshold(): void
    {
        $this->seedResults(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => ['https://store2.example.com/' => 'HTTP 500'],
            ]
        );

        $output = $this->createCheck(statusTimeThreshold: 5)->run();

        $this->assertEquals(
            CheckStatus::STATUS_OK,
            $output->getStatus(),
            'A fresh failure should not alert until it persists for the status time threshold (flapping protection)'
        );
        $this->assertArrayHasKey(
            'failed_urls',
            $output->getMeta(),
            'The failing URLs should already be visible in the meta while the status change is being damped'
        );
    }

    public function testWarningWhenResultsAreStale(): void
    {
        $this->seedResults(
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

    private function createCheck(int $statusTimeThreshold = 0): StoreFronts
    {
        return Bootstrap::getObjectManager()->create(
            StoreFronts::class,
            ['statusTimeThreshold' => $statusTimeThreshold]
        );
    }

    private function seedResults(array $data): void
    {
        $this->cacheService->saveCheckData(
            StoreFronts::RESULTS_CACHE_KEY,
            $data['failed'] === [] ? CheckStatus::STATUS_OK->value : CheckStatus::STATUS_FAILED->value,
            $data
        );
    }

    private function seedResolverState(string $status, int $time): void
    {
        $this->cacheService->saveCheckData(StoreFronts::CHECK_NAME, $status, (string)$time);
    }

    private function clearCache(): void
    {
        $this->cacheService->removeCheckData(StoreFronts::RESULTS_CACHE_KEY);
        $this->cacheService->removeCheckData(StoreFronts::CHECK_NAME);
    }
}

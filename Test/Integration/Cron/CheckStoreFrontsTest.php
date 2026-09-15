<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Cron;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Checks\StoreFronts;
use Vendic\OhDear\Cron\CheckStoreFronts;
use Vendic\OhDear\Model\StoreFronts\HttpStatusFetcher;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Service\StoreFrontChecker;

class CheckStoreFrontsTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        $this->cacheService = Bootstrap::getObjectManager()->get(CacheService::class);
        $this->cacheService->removeCheckData(StoreFronts::RESULTS_CACHE_KEY);
    }

    protected function tearDown(): void
    {
        $this->cacheService->removeCheckData(StoreFronts::RESULTS_CACHE_KEY);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixture_second_store_store web/secure/base_link_url https://second.example.com/
     */
    public function testStoresFailedUrlsForTheStoreFrontsCheck(): void
    {
        $this->runCron(['https://second.example.com/' => ['status' => 503, 'error' => null]]);

        $results = $this->readResults();
        $this->assertEquals(['https://second.example.com/' => 'HTTP 503'], $results['failed']);
        $this->assertGreaterThanOrEqual(1, $results['checked_count']);
        $this->assertEqualsWithDelta(time(), $results['checked_at'], 10);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixture_second_store_store web/secure/base_link_url https://second.example.com/
     */
    public function testStoresOkResultWhenAllStoreFrontsAreReachable(): void
    {
        $this->runCron(['https://second.example.com/' => ['status' => 200, 'error' => null]]);

        $this->assertSame([], $this->readResults()['failed']);
    }

    public function testDoesNotCheckAnythingWhenTheStoreFrontsCheckIsDisabled(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $deploymentConfig = $this->createMock(\Magento\Framework\App\DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturnCallback(
            fn (?string $key = null) => $key === 'ohdear' ? [StoreFronts::class => ['enabled' => false]] : null
        );

        $fetcher = $this->createMock(HttpStatusFetcher::class);
        $fetcher->expects($this->never())->method('fetch');

        /** @var CheckStoreFronts $cron */
        $cron = $objectManager->create(
            CheckStoreFronts::class,
            [
                'configuration' => $objectManager->create(
                    \Vendic\OhDear\Utils\Configuration::class,
                    ['deploymentConfig' => $deploymentConfig]
                ),
                'storeFrontChecker' => $objectManager->create(
                    StoreFrontChecker::class,
                    ['httpStatusFetcher' => $fetcher]
                ),
            ]
        );
        $cron->execute();
    }

    /**
     * @param array<string, array{status: int, error: ?string}> $responsesByUrl
     */
    private function runCron(array $responsesByUrl): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $fetcher = $this->createMock(HttpStatusFetcher::class);
        $fetcher->method('fetch')->willReturnCallback(
            fn (array $urls): array => array_intersect_key($responsesByUrl, array_flip($urls))
        );

        /** @var CheckStoreFronts $cron */
        $cron = $objectManager->create(
            CheckStoreFronts::class,
            [
                'storeFrontChecker' => $objectManager->create(
                    StoreFrontChecker::class,
                    ['httpStatusFetcher' => $fetcher]
                ),
            ]
        );
        $cron->execute();
    }

    private function readResults(): array
    {
        $cached = $this->cacheService->getDataForCheck(StoreFronts::RESULTS_CACHE_KEY);
        $this->assertIsArray($cached['data'] ?? null, 'Expected store front results to be cached');

        return $cached['data'];
    }
}

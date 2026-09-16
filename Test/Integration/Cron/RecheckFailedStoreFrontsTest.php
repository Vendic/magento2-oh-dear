<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Cron;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\StoreFronts;
use Vendic\OhDear\Cron\RecheckFailedStoreFronts;
use Vendic\OhDear\Model\StoreFronts\HttpStatusFetcher;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Service\StoreFrontChecker;

class RecheckFailedStoreFrontsTest extends TestCase
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

    public function testRechecksOnlyTheFailedUrlsAndClearsRecoveredOnes(): void
    {
        $sweepTime = time() - 1800;
        $this->seedResults(
            [
                'checked_at' => $sweepTime,
                'checked_count' => 3,
                'failed' => [
                    'https://store2.example.com/' => 'HTTP 500',
                    'https://store3.example.com/' => 'HTTP 503',
                ],
            ]
        );

        $this->runCron(
            expectedUrls: ['https://store2.example.com/', 'https://store3.example.com/'],
            responsesByUrl: [
                'https://store2.example.com/' => ['status' => 200, 'error' => null],
                'https://store3.example.com/' => ['status' => 503, 'error' => null],
            ]
        );

        $results = $this->readResults();
        $this->assertSame(
            ['https://store3.example.com/' => 'HTTP 503'],
            $results['failed'],
            'Recovered store fronts should be cleared, still failing ones kept'
        );
        $this->assertSame(3, $results['checked_count'], 'The full sweep store front count should be preserved');
        $this->assertSame($sweepTime, $results['checked_at'], 'The full sweep timestamp should be preserved');
    }

    public function testDoesNothingWhenThereAreNoFailedUrls(): void
    {
        $this->seedResults(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => [],
            ]
        );

        $this->runCron(expectedUrls: null, responsesByUrl: []);

        $this->assertSame([], $this->readResults()['failed']);
    }

    public function testDoesNothingWhenThereAreNoResultsYet(): void
    {
        $this->runCron(expectedUrls: null, responsesByUrl: []);

        $this->assertNull($this->cacheService->getDataForCheck(StoreFronts::RESULTS_CACHE_KEY));
    }

    public function testDoesNotRecheckWhenTheStoreFrontsCheckIsDisabled(): void
    {
        $this->seedResults(
            [
                'checked_at' => time(),
                'checked_count' => 3,
                'failed' => ['https://store2.example.com/' => 'HTTP 500'],
            ]
        );

        $objectManager = Bootstrap::getObjectManager();

        $deploymentConfig = $this->createMock(\Magento\Framework\App\DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturnCallback(
            fn (?string $key = null) => $key === 'ohdear' ? [StoreFronts::class => ['enabled' => false]] : null
        );

        $fetcher = $this->createMock(HttpStatusFetcher::class);
        $fetcher->expects($this->never())->method('fetch');

        /** @var RecheckFailedStoreFronts $cron */
        $cron = $objectManager->create(
            RecheckFailedStoreFronts::class,
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
     * @param string[]|null $expectedUrls the exact URLs the cron is expected to recheck, null when none
     * @param array<string, array{status: int, error: ?string}> $responsesByUrl
     */
    private function runCron(?array $expectedUrls, array $responsesByUrl): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $fetcher = $this->createMock(HttpStatusFetcher::class);
        if ($expectedUrls === null) {
            $fetcher->expects($this->never())->method('fetch');
        } else {
            $fetcher->expects($this->once())
                ->method('fetch')
                ->with($expectedUrls)
                ->willReturn($responsesByUrl);
        }

        /** @var RecheckFailedStoreFronts $cron */
        $cron = $objectManager->create(
            RecheckFailedStoreFronts::class,
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

    private function seedResults(array $data): void
    {
        $this->cacheService->saveCheckData(
            StoreFronts::RESULTS_CACHE_KEY,
            $data['failed'] === [] ? CheckStatus::STATUS_OK->value : CheckStatus::STATUS_FAILED->value,
            $data
        );
    }
}

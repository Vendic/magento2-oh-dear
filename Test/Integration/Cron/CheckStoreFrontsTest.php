<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Cron;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\StoreFronts;
use Vendic\OhDear\Cron\CheckStoreFronts;
use Vendic\OhDear\Model\StoreFronts\HttpStatusFetcher;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Service\StoreFrontChecker;

class CheckStoreFrontsTest extends TestCase
{
    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->get(CacheService::class)->removeCheckData(StoreFronts::CHECK_NAME);
    }

    protected function tearDown(): void
    {
        Bootstrap::getObjectManager()->get(CacheService::class)->removeCheckData(StoreFronts::CHECK_NAME);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/secure/base_url https://second.example.com/
     */
    public function testStoresFailedDomainsForTheStoreFrontsCheck(): void
    {
        $this->runCron(['https://second.example.com/' => ['status' => 503, 'error' => null]]);

        $output = $this->runCheck();

        $this->assertEquals(CheckStatus::STATUS_FAILED, $output->getStatus());
        $this->assertEquals(
            ['second.example.com' => 'HTTP 503'],
            $output->getMeta()['failed_domains']
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/secure/base_url https://second.example.com/
     */
    public function testStoresOkResultWhenAllStoreFrontsAreReachable(): void
    {
        $this->runCron(['https://second.example.com/' => ['status' => 200, 'error' => null]]);

        $output = $this->runCheck();

        $this->assertEquals(CheckStatus::STATUS_OK, $output->getStatus());
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

    private function runCheck(): \Vendic\OhDear\Api\Data\CheckResultInterface
    {
        return Bootstrap::getObjectManager()->create(StoreFronts::class)->run();
    }
}

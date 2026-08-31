<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Model\StoreFronts;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Model\StoreFronts\UrlProvider;

class UrlProviderTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/secure/base_url https://second.example.com/
     */
    public function testReturnsChildStoreUrls(): void
    {
        $this->assertContains('https://second.example.com/', $this->getUrls());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/secure/base_url https://second.example.com/
     */
    public function testExcludesTheDefaultStoreDomain(): void
    {
        $defaultBaseUrl = Bootstrap::getObjectManager()
            ->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getDefaultStoreView()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
        $defaultHost = parse_url($defaultBaseUrl, PHP_URL_HOST);

        foreach ($this->getUrls() as $url) {
            $this->assertNotEquals(
                $defaultHost,
                parse_url($url, PHP_URL_HOST),
                'The default store domain is already monitored by Oh Dear and should be excluded'
            );
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/store.php
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture test_store web/secure/base_url https://shared.example.com/
     * @magentoConfigFixture fixture_second_store_store web/secure/base_url https://shared.example.com/
     */
    public function testDeduplicatesStoresOnTheSameDomain(): void
    {
        $sharedDomainUrls = array_filter(
            $this->getUrls(),
            fn (string $url): bool => parse_url($url, PHP_URL_HOST) === 'shared.example.com'
        );

        $this->assertCount(1, $sharedDomainUrls, 'Stores sharing a domain should only be checked once');
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/inactive_store.php
     * @magentoConfigFixture inactive_store_store web/secure/base_url https://inactive.example.com/
     */
    public function testExcludesInactiveStores(): void
    {
        $this->assertNotContains('https://inactive.example.com/', $this->getUrls());
    }

    /**
     * @return string[]
     */
    private function getUrls(): array
    {
        /** @var UrlProvider $urlProvider */
        $urlProvider = Bootstrap::getObjectManager()->create(UrlProvider::class);

        return $urlProvider->getUrls();
    }
}

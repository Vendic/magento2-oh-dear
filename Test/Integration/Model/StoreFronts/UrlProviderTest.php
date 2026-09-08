<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Model\StoreFronts;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Model\StoreFronts\UrlProvider;

class UrlProviderTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixture_second_store_store web/url/use_store 1
     */
    public function testReturnsStoreCodePrefixedUrlsOnTheDefaultDomain(): void
    {
        $this->assertContains(
            $this->getDefaultStoreBaseUrl() . 'fixture_second_store/',
            $this->getUrls(),
            'A store without a custom base URL should be checked on the default domain with its store code path'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixture_second_store_store web/secure/base_link_url https://second.example.com/
     * @magentoConfigFixture fixture_second_store_store web/unsecure/base_link_url https://second.example.com/
     */
    public function testUsesTheCustomBaseUrlWhenPresent(): void
    {
        $this->assertContains('https://second.example.com/', $this->getUrls());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixture_second_store_store web/url/use_store 1
     * @magentoConfigFixture fixture_second_store_store web/secure/base_link_url https://second.example.com/
     * @magentoConfigFixture fixture_second_store_store web/unsecure/base_link_url https://second.example.com/
     */
    public function testAppendsTheStoreCodeToTheCustomBaseUrlWhenStoreCodeInUrlIsEnabled(): void
    {
        $this->assertContains(
            'https://second.example.com/fixture_second_store/',
            $this->getUrls(),
            'The checked URL should match the links Magento itself generates for the store'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store web/secure/base_link_url https://second.example.com/
     * @magentoConfigFixture fixture_second_store_store web/unsecure/base_link_url https://second.example.com/
     */
    public function testExcludesTheDefaultStoreView(): void
    {
        $defaultStoreUrl = $this->getDefaultStoreView()->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);

        $this->assertNotContains(
            $defaultStoreUrl,
            $this->getUrls(),
            'The default store view is already monitored by Oh Dear and should be excluded'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/store.php
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture test_store web/seo/use_rewrites 1
     * @magentoConfigFixture test_store web/secure/base_link_url https://shared.example.com/
     * @magentoConfigFixture test_store web/unsecure/base_link_url https://shared.example.com/
     * @magentoConfigFixture fixture_second_store_store web/seo/use_rewrites 1
     * @magentoConfigFixture fixture_second_store_store web/secure/base_link_url https://shared.example.com/
     * @magentoConfigFixture fixture_second_store_store web/unsecure/base_link_url https://shared.example.com/
     */
    public function testDeduplicatesStoresWithTheSameUrl(): void
    {
        $sharedUrls = array_filter(
            $this->getUrls(),
            fn (string $url): bool => $url === 'https://shared.example.com/'
        );

        $this->assertCount(1, $sharedUrls, 'Stores resolving to the same URL should only be checked once');
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Store/_files/inactive_store.php
     * @magentoConfigFixture inactive_store_store web/secure/base_link_url https://inactive.example.com/
     * @magentoConfigFixture inactive_store_store web/unsecure/base_link_url https://inactive.example.com/
     */
    public function testExcludesInactiveStores(): void
    {
        foreach ($this->getUrls() as $url) {
            $this->assertStringNotContainsString('inactive.example.com', $url);
        }
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

    private function getDefaultStoreBaseUrl(): string
    {
        return $this->getDefaultStoreView()->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
    }

    private function getDefaultStoreView(): \Magento\Store\Model\Store
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getDefaultStoreView();

        return $store;
    }
}

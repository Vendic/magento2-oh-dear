<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Model\StoreFronts;

use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class UrlProvider
{
    public function __construct(private StoreManagerInterface $storeManager)
    {
    }

    /**
     * @return string[]
     */
    public function getUrls(): array
    {
        $defaultStoreView = $this->storeManager->getDefaultStoreView();
        $baseUrls = [];

        foreach ($this->storeManager->getStores() as $store) {
            if (!$this->isActive($store)) {
                continue;
            }

            $baseUrl = $this->getBaseUrl($store);
            if ($baseUrl === null || $baseUrl === $defaultStoreView || in_array($baseUrl, $baseUrls)) {
                continue;
            }

            $baseUrls[] = $this->getBaseUrl($store);
        }

        return $baseUrls;
    }

    private function isActive(StoreInterface $store): bool
    {
        return $store instanceof Store ? $store->isActive() : (bool)$store->getIsActive();
    }

    private function getHost(StoreInterface $store): ?string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $host = parse_url($this->getBaseUrl($store), PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }

    private function getBaseUrl(StoreInterface $store): string
    {
        /** @var Store $store */
        return $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
    }
}

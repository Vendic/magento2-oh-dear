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
use Vendic\OhDear\Utils\Configuration;
use Vendic\OhDear\Checks\StoreFronts;

class UrlProvider
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private Configuration $configuration,
    ) {
    }

    /**
     * @return string[]
     */
    public function getUrls(): array
    {
        $defaultStoreId = (int)$this->storeManager->getDefaultStoreView()?->getId();
        $urls = [];

        foreach ($this->storeManager->getStores() as $store) {
            if (
                (int)$store->getId() === $defaultStoreId
                || !$this->isActive($store)
                || in_array(
                    $store->getCode(),
                    $this->configuration->getCheckConfigValue(StoreFronts::class, "excluded_stores") ?? []
                )
            ) {
                continue;
            }

            $url = $this->getLinkUrl($store);
            if (!in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function isActive(StoreInterface $store): bool
    {
        return $store instanceof Store ? $store->isActive() : (bool)$store->getIsActive();
    }

    private function getLinkUrl(StoreInterface $store): string
    {
        /** @var Store $store */
        return $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);
    }
}

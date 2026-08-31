<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Cron;

use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\StoreFronts;
use Vendic\OhDear\Model\StoreFronts\UrlProvider;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Service\StoreFrontChecker;
use Vendic\OhDear\Utils\Configuration;

class CheckStoreFronts
{
    public function __construct(
        private UrlProvider $urlProvider,
        private StoreFrontChecker $storeFrontChecker,
        private CacheService $cacheService,
        private Configuration $configuration,
        private StoreFronts $storeFrontsCheck
    ) {
    }

    public function execute(): void
    {
        if (!$this->configuration->isCheckEnabled($this->storeFrontsCheck)) {
            return;
        }

        $result = $this->storeFrontChecker->check($this->urlProvider->getUrls());

        $this->cacheService->saveCheckData(
            StoreFronts::CHECK_NAME,
            $result['failed'] === [] ? CheckStatus::STATUS_OK->value : CheckStatus::STATUS_FAILED->value,
            $result
        );
    }
}

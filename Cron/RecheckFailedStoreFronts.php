<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Cron;

use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\StoreFronts;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Service\StoreFrontChecker;
use Vendic\OhDear\Utils\Configuration;

/**
 * Rechecks only the store fronts that failed during the last full sweep, so
 * recoveries and persisting outages are noticed within minutes instead of
 * waiting for the next hourly sweep. Does nothing while everything is up.
 */
class RecheckFailedStoreFronts
{
    public function __construct(
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

        $cached = $this->cacheService->getDataForCheck(StoreFronts::RESULTS_CACHE_KEY);
        $results = is_array($cached['data'] ?? null) ? $cached['data'] : null;
        $failedUrls = array_keys((array)($results['failed'] ?? []));

        if ($results === null || $failedUrls === []) {
            return;
        }

        $results['failed'] = $this->storeFrontChecker->check($failedUrls)['failed'];

        $this->cacheService->saveCheckData(
            StoreFronts::RESULTS_CACHE_KEY,
            $results['failed'] === [] ? CheckStatus::STATUS_OK->value : CheckStatus::STATUS_FAILED->value,
            $results
        );
    }
}

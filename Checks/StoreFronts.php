<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CachedStatusResolver;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Utils\Configuration;

class StoreFronts implements CheckInterface
{
    public const CHECK_NAME = 'store_fronts';
    public const RESULTS_CACHE_KEY = 'store_fronts_results';

    public function __construct(
        private CacheService $cacheService,
        private CheckResultFactory $checkResultFactory,
        private CachedStatusResolver $cachedStatusResolver,
        private Configuration $configuration,
        private int $statusTimeThreshold = 5,
        private int $maxResultAgeSeconds = 7200
    ) {
    }

    public function run(): CheckResultInterface
    {
        /** @var CheckResultInterface $checkResult */
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName(self::CHECK_NAME);
        $checkResult->setLabel('Store fronts');
        $checkResult->setMeta([]);

        $cachedCheck = $this->cacheService->getDataForCheck(self::RESULTS_CACHE_KEY);
        $results = is_array($cachedCheck['data'] ?? null) ? $cachedCheck['data'] : null;

        if ($results === null) {
            $checkResult->setStatus(CheckStatus::STATUS_OK);
            $checkResult->setShortSummary('Store front check has not run yet');
            $checkResult->setNotificationMessage(
                'No store front results found in cache, the store front check cron may not be running'
            );
            return $checkResult;
        }

        $checkedAt = (int)($results['checked_at'] ?? 0);
        $checkedCount = (int)($results['checked_count'] ?? 0);
        $failedUrls = (array)($results['failed'] ?? []);

        if (!$checkedCount) {
            $checkResult->setStatus(CheckStatus::STATUS_OK);
            $checkResult->setShortSummary('There are no children stores to check');
            $checkResult->setNotificationMessage(
                'No children store fronts available, thus nothing to check'
            );
            return $checkResult;
        }

        if ($failedUrls === [] && $checkedAt < time() - $this->maxResultAgeSeconds) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setShortSummary('Store front results are stale');
            $checkResult->setNotificationMessage(
                sprintf(
                    'Last store front check ran at %s, the store front check cron may not be running',
                    date('Y-m-d H:i:s', $checkedAt)
                )
            );
            $checkResult->setMeta(['checked_at' => $checkedAt]);
            return $checkResult;
        }

        return $this->processStatus($checkResult, $failedUrls, $checkedCount, $checkedAt);
    }

    /**
     * @param string[] $failedUrls
     */
    private function processStatus(
        CheckResultInterface $checkResult,
        array $failedUrls,
        int $checkedCount,
        int $checkedAt
    ): CheckResultInterface {
        $this->cachedStatusResolver->setStatusTimeThreshold($this->getStatusTimeThreshold());
        $this->cachedStatusResolver->setMessagesByStatus(
            [
                CachedStatusResolver::STATUS_FAIL => [
                    'summary' => '%s: %s children store front(s) down',
                    'notification_message' => '%s has children store fronts down (%s)',
                ],
            ]
        );

        if ($failedUrls !== []) {
            $checkResult->setMeta(
                [
                    'failed_urls' => $failedUrls,
                    'checked_count' => $checkedCount,
                    'checked_at' => $checkedAt,
                ]
            );

            return $this->cachedStatusResolver->updateCacheCheck(
                $checkResult,
                CheckStatus::STATUS_FAILED,
                count($failedUrls)
            );
        }

        $checkResult->setMeta(
            [
                'checked_count' => $checkedCount,
                'checked_at' => $checkedAt,
            ]
        );

        return $this->cachedStatusResolver->updateCacheCheck($checkResult, CheckStatus::STATUS_OK);
    }

    private function getStatusTimeThreshold(): int
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'status_time_treshold');

        return is_numeric($configValue) ? (int)$configValue : $this->statusTimeThreshold;
    }
}

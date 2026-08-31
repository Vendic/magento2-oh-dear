<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Service\CacheService;

class StoreFronts implements CheckInterface
{
    public const CHECK_NAME = 'store_fronts';

    public function __construct(
        private CacheService $cacheService,
        private CheckResultFactory $checkResultFactory,
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

        $cachedCheck = $this->cacheService->getDataForCheck(self::CHECK_NAME);
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
        $failedDomains = (array)($results['failed'] ?? []);

        if (!$checkedCount) {
            $checkResult->setStatus(CheckStatus::STATUS_OK);
            $checkResult->setShortSummary('There are no children stores to check');
            $checkResult->setNotificationMessage(
                'No children store fronts available, thus nothing to check'
            );
            return $checkResult;
        }

        if ($failedDomains !== []) {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setShortSummary(
                sprintf('%d of %d children store front(s) down', count($failedDomains), $checkedCount)
            );
            $checkResult->setNotificationMessage(
                sprintf('Children store fronts down: %s', implode(', ', array_keys($failedDomains)))
            );
            $checkResult->setMeta(
                [
                    'failed_domains' => $failedDomains,
                    'checked_count' => $checkedCount,
                    'checked_at' => $checkedAt,
                ]
            );
            return $checkResult;
        }

        if ($checkedAt < time() - $this->maxResultAgeSeconds) {
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

        $checkResult->setStatus(CheckStatus::STATUS_OK);
        $checkResult->setShortSummary('All store fronts reachable');
        $checkResult->setNotificationMessage(
            sprintf('All %d store front(s) are reachable', $checkedCount)
        );
        $checkResult->setMeta(
            [
                'checked_count' => $checkedCount,
                'checked_at' => $checkedAt,
            ]
        );

        return $checkResult;
    }
}

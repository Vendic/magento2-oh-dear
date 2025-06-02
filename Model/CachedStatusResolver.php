<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Model;

use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Service\CacheService;

class CachedStatusResolver
{
    /** #@+
     * Status Names.
     *
     * @const
     */
    public const STATUS_OK = 'ok';
    public const STATUS_CHANGE = 'status_change';
    public const STATUS_IN_THRESHOLD = 'status_in_threshold';
    public const STATUS_FAIL = 'status_fail';
    /** #@- */

    public const STATUSES_BY_SEVERITY = [
        CheckStatus::STATUS_FAILED->value => 50,
        CheckStatus::STATUS_WARNING->value => 40,
        CheckStatus::STATUS_CRASHED->value => 30,
        CheckStatus::STATUS_SKIPPED->value => 20,
        CheckStatus::STATUS_OK->value => 10,
    ];

    private const MESSAGES_BY_STATUS_DEFAULT = [
        self::STATUS_OK => [
            'summary' => '%s is OK',
        ],
        self::STATUS_CHANGE => [
            'summary' => '%s change status',
            'notification_message' => '%s status changed to %s (value of %s)',
        ],
        self::STATUS_IN_THRESHOLD => [
            'summary' => '%s status may become %s',
            'notification_message' => '%s status is %s (value of %s), does not exceed the threshold of %sminutes',
        ],
        self::STATUS_FAIL => [
            'summary' => '%s is too high, (%s)',
            'notification_message' => '%s value is %s',
        ],
    ];

    private int $statusTimeThreshold = 0;

    public function __construct(
        private CacheService $cacheService,
        private array $messagesByStatus = self::MESSAGES_BY_STATUS_DEFAULT,
    ) {
    }

    public function updateCacheCheck(
        CheckResultInterface $checkResult,
        CheckStatus $status,
        int $checkValue = 0
    ): CheckResultInterface {
        $checkCache = $this->cacheService->getDataForCheck($checkResult->getName());

        // Everything is ok, remove the cache
        if ($status === CheckStatus::STATUS_OK) {
            $checkResult->setStatus(CheckStatus::STATUS_OK);
            $checkResult->setShortSummary(
                sprintf(
                    $this->getMessagesByStatus()[self::STATUS_OK]['summary'],
                    $checkResult->getLabel()
                )
            );
            $this->cacheService->removeCheckData($checkResult->getName());

            return $checkResult;
        }

        // Status changed, reset time for selected status
        if (($checkCache['status'] ?? null) !== $status->value) {
            $cachedStatus = $checkCache['status'] ?? CheckStatus::STATUS_OK->value;
            $this->cacheService->updateCheckData($checkResult->getName(), $status->value, (string)time());

            $checkResult->setStatus($this->getFlappingStatus($cachedStatus, $status->value));
            $checkResult->setShortSummary(
                sprintf($this->getMessagesByStatus()[self::STATUS_CHANGE]['summary'], $checkResult->getLabel())
            );
            $checkResult->setNotificationMessage(
                sprintf(
                    $this->getMessagesByStatus()[self::STATUS_CHANGE]['notification_message'],
                    $checkResult->getLabel(),
                    $status->value,
                    $checkValue
                )
            );

            return $checkResult;
        }

        // Status does not exceed the threshold, keep as it is
        if ((int)$checkCache['data'] > (time() - $this->getStatusTimeThreshold() * 60)) {
            $checkResult->setStatus($this->getFlappingStatus(
                $checkCache['fallback_status'],
                $status->value
            ));
            $checkResult->setShortSummary(
                sprintf(
                    $this->getMessagesByStatus()[self::STATUS_IN_THRESHOLD]['summary'],
                    $checkResult->getLabel(),
                    $status->value
                )
            );
            $checkResult->setNotificationMessage(
                sprintf(
                    $this->getMessagesByStatus()[self::STATUS_IN_THRESHOLD]['notification_message'],
                    $checkResult->getLabel(),
                    $status->value,
                    $checkValue,
                    $this->getStatusTimeThreshold()
                )
            );

            return $checkResult;
        }

        // Status exceed the threshold, notify of the event
        $checkResult->setStatus($status);
        $checkResult->setNotificationMessage(
            sprintf(
                $this->getMessagesByStatus()[self::STATUS_FAIL]['summary'],
                $checkResult->getLabel(),
                $checkValue
            )
        );
        $checkResult->setShortSummary(
            sprintf(
                $this->getMessagesByStatus()[self::STATUS_FAIL]['notification_message'],
                $checkResult->getLabel(),
                $status->value
            )
        );
        return $checkResult;
    }

    public function getMessagesByStatus(): array
    {
        return $this->messagesByStatus;
    }

    public function setMessagesByStatus(array $messagesByStatus): self
    {
        $this->messagesByStatus = array_replace_recursive(
            $this->messagesByStatus,
            $messagesByStatus
        );

        return $this;
    }

    public function getFlappingStatus(string $oldStatus, string $newStatus): CheckStatus
    {
        $statusToSet = CheckStatus::STATUS_OK->name;
        if (self::STATUSES_BY_SEVERITY[$oldStatus] < self::STATUSES_BY_SEVERITY[$newStatus]) {
            $statusToSet = "STATUS_" . strtoupper($oldStatus);
        } else {
            $statusToSet = "STATUS_" . strtoupper($newStatus);
        }

        return constant("\Vendic\OhDear\Api\Data\CheckStatus::{$statusToSet}");
    }

    private function getStatusTimeThreshold(): int
    {
        return $this->statusTimeThreshold;
    }

    public function setStatusTimeThreshold(int $statusTimeThreshold): self
    {
        $this->statusTimeThreshold = $statusTimeThreshold;
        return $this;
    }
}

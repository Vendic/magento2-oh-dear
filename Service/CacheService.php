<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Service;

use Magento\Framework\App\CacheInterface;
use Vendic\OhDear\Api\Data\CheckStatus;

class CacheService
{
    private const OD_CACHE_PREFIX = 'oh_dear';

    private const DATA_SEPARATOR = '==>>';

    // Checks statuses sorted by decreasing severity level.
    private const STATUSES = [
        CheckStatus::STATUS_CRASHED,
        CheckStatus::STATUS_FAILED,
        CheckStatus::STATUS_WARNING,
        CheckStatus::STATUS_SKIPPED,
        CheckStatus::STATUS_OK,
    ];

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param string $checkKey
     * @return array{
     *     severity: CheckStatus,
     *     data: string
     * }|null
     */
    public function getDataForCheck(string $checkKey): ?array
    {
        $result = [
            'severity' => null,
            'data' => null
        ];

        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;

        if (!$cacheData = $this->cache->load($identifier)) {
            return null;
        }

        [$severity, $data] = explode(self::DATA_SEPARATOR, $cacheData, 2);

        $result['data'] = $data;
        $result['severity'] = $severity;

        return $result['data'] ? $result : null;
    }

    public function removeCheckData(string $checkKey): bool
    {
        $result = false;
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;

        return $this->cache->remove($identifier);
    }

    public function saveCheckData(string $checkKey, string $status, string $data): bool
    {
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;
        return $this->cache->save($status . self::DATA_SEPARATOR . $data, $identifier);
    }

    public function updateCheckData(string $checkKey, string $status, string $data)
    {
        return $this->saveCheckData($checkKey, $status, $data);
    }
}

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

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param string $checkKey
     * @return array{
     *     status: string,
     *     data: string
     * }|null
     */
    public function getDataForCheck(string $checkKey): ?array
    {
        $result = [
            'status' => null,
            'data' => null
        ];

        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;
        $fallbackRecordIdentifier = self::OD_CACHE_PREFIX . '_fallback_' . $checkKey;

        if (!$cacheData = $this->cache->load($identifier)) {
            return null;
        }

        $fallbackData = $this->cache->load($fallbackRecordIdentifier) ?? null;

        [$severity, $data] = explode(self::DATA_SEPARATOR, $cacheData, 2);

        $fallbackSeverity = null;
        if ($fallbackData) {
            [$fallbackSeverity] = explode(self::DATA_SEPARATOR, $fallbackData);
        }

        $result['data'] = $data;
        $result['status'] = $severity;
        $result['fallback_status'] = $fallbackSeverity ?? CheckStatus::STATUS_OK->value;

        return $result['data'] ? $result : null;
    }

    public function removeCheckData(string $checkKey, bool $removeFallback = false): bool
    {
        $result = false;
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;
        $fallbackIdentifier = self::OD_CACHE_PREFIX . '_fallback_' . $checkKey;

        if (($cachedData = $this->cache->load($identifier)) && !$removeFallback) {
            $this->cache->save($cachedData, $fallbackIdentifier);
        }

        if ($removeFallback) {
            $this->cache->remove($fallbackIdentifier);
        }

        return $this->cache->remove($identifier);
    }

    public function saveCheckData(string $checkKey, string $status, string $data): bool
    {
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;
        $fallbackRecordIdentifier = self::OD_CACHE_PREFIX . '_fallback_' . $checkKey;

        if ($currentData = $this->cache->load($identifier)) {
            $this->cache->save($currentData, $fallbackRecordIdentifier);
        }
        return $this->cache->save($status . self::DATA_SEPARATOR . $data, $identifier);
    }

    public function updateCheckData(string $checkKey, string $status, string $data)
    {
        return $this->saveCheckData($checkKey, $status, $data);
    }
}

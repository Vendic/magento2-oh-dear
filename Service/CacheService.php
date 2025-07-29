<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Vendic\OhDear\Api\Data\CheckStatus;

class CacheService
{
    private const OD_CACHE_PREFIX = 'oh_dear';

    private const DATA_SEPARATOR = '==>>';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Json $serializer
    ) {
    }

    /**
     * @return array{
     *     status: string,
     *     fallback_status: string,
     *     data: string
     * }|null
     */
    public function getDataForCheck(string $checkKey): ?array
    {
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;

        if (!$cacheData = $this->cache->load($identifier)) {
            return null;
        }

        $result = $this->serializer->unserialize($cacheData);
        $result['fallback_status'] = $result['fallback_data']['status'] ?? CheckStatus::STATUS_OK->value;

        return $result['data'] ? $result : null;
    }

    public function removeCheckData(string $checkKey, bool $removeFallback = true): bool
    {
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;

        if (
            ($cacheRecord = $this->cache->load($identifier))
            && ($cachedData = $this->serializer->unserialize($cacheRecord))
            && isset($cachedData['fallback_data'])
            && !$removeFallback
        ) {
            return $this->cache->save(
                $this->serializer->serialize($cachedData['fallback_data']),
                $identifier
            );
        }

        return $this->cache->remove($identifier);
    }

    public function saveCheckData(
        string $checkKey,
        string $status,
        string|array $data,
        bool $persistFallback = false
    ): bool {
        $identifier = self::OD_CACHE_PREFIX . '_' . $checkKey;

        $payload = [
            "status" => $status,
            "data" => $data,
        ];

        try {
            if (
                ($currentData = $this->cache->load($identifier))
                && $currentData = $this->serializer->unserialize($currentData)
            ) {
                $payload['fallback_data'] = $currentData;
            }

            if (
                isset($currentData['fallback_data'])
                && $persistFallback
            ) {
                $payload['fallback_data'] = $currentData['fallback_data'];
            }

            unset($payload['fallback_data']['fallback_data']);
        } catch (\Exception $exception) {
            unset($payload['fallback_data']);
        }

        return $this->cache->save($this->serializer->serialize($payload), $identifier);
    }
}

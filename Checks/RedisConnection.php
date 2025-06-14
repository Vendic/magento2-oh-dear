<?php declare(strict_types=1);

namespace Vendic\OhDear\Checks;

use Magento\Framework\App\DeploymentConfig;
use Magento\Setup\Model\ConfigOptionsList\Cache;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
class RedisConnection implements CheckInterface
{
    public const CONFIG_VALUE_CACHE_REDIS_LEGACY = 'Cm_Cache_Backend_Redis';

    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private Cache $cache,
        private CheckResultFactory $checkResultFactory,
        private bool $enforceRedis = false
    ) {
    }

    public function run(): CheckResultInterface
    {
        $deploymentConfig = $this->deploymentConfig;
        $options = [];
        /** @var CheckResultInterface $checkResult */

        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('redis_connection');
        $checkResult->setLabel('Redis connection');
        $checkResult->setMeta(
            [
                'host' => $deploymentConfig->get('cache/frontend/default/backend_options/server'),
                'port' => $deploymentConfig->get('cache/frontend/default/backend_options/port'),
                'type' => $this->getBackendCacheType($deploymentConfig)
            ]
        );

        if ($this->checkisRedisEnabled($deploymentConfig) === false) {
            $checkResult->setStatus(
                $this->enforceRedis ? CheckStatus::STATUS_FAILED : CheckStatus::STATUS_SKIPPED
            );
            $checkResult->setShortSummary('Redis disabled');
            $checkResult->setNotificationMessage('Redis is not enabled');
            return $checkResult;
        }

        if ($this->isLegacyRedis($deploymentConfig)) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setShortSummary('Legacy Redis configuration detected');
            $checkResult->setNotificationMessage(
                sprintf('Legacy Redis configuration detected, switch to %s', Cache::CONFIG_VALUE_CACHE_REDIS)
            );
            return $checkResult;
        }

        $connectionErrors = $this->cache->validate($options, $deploymentConfig);
        $status = count($connectionErrors) === 0 ? CheckStatus::STATUS_OK : CheckStatus::STATUS_FAILED;

        $checkResult->setStatus($status);
        $checkResult->setShortSummary(
            $status === CheckStatus::STATUS_OK ? 'Redis OK' : 'Redis connection error'
        );
        $checkResult->setNotificationMessage(
            $status == CheckStatus::STATUS_OK ? 'Redis connection OK' : 'Redis connection not OK'
        );

        return $checkResult;
    }

    private function checkisRedisEnabled(DeploymentConfig $deploymentConfig): bool
    {
        $currentCacheBackend = $this->getBackendCacheType($deploymentConfig);

        return in_array(
            $currentCacheBackend,
            [
                Cache::CONFIG_VALUE_CACHE_REDIS,
                RedisConnection::CONFIG_VALUE_CACHE_REDIS_LEGACY
            ]
        );
    }

    private function isLegacyRedis(DeploymentConfig $deploymentConfig): bool
    {
        return $this->getBackendCacheType($deploymentConfig) === RedisConnection::CONFIG_VALUE_CACHE_REDIS_LEGACY;
    }

    private function getBackendCacheType(DeploymentConfig $deploymentConfig): mixed
    {
        return $deploymentConfig->get(Cache::CONFIG_PATH_CACHE_BACKEND);
    }
}

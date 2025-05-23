<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Magento\Framework\App\ResourceConnection;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CachedStatusResolver;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Utils\Configuration;
use Vendic\OhDear\Utils\Database as DatabseUtils;

class DatabaseConnectionCount implements CheckInterface
{
    public const OK_SUMMARY = 'Database connection count OK';
    public const CONNECTIONS_TO_HIGH_SUMMARY = 'Database connection count is too high';

    public function __construct(
        private CheckResultFactory $checkResultFactory,
        private DatabseUtils $databaseUtils,
        private Configuration $configuration,
        private int $warningThreshold,
        private int $failedTreshold,
        private int $statusTimeThreshold,
        private CachedStatusResolver $cachedStatusResolver,
        private CacheService $cacheService
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('db_connection_count');
        $checkResult->setLabel('DB connection count');

        try {
            $connectionCount = $this->databaseUtils->getConnectionCount();
        } catch (\Exception $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setNotificationMessage($e->getMessage());
            $checkResult->setShortSummary('Could not get database connection count');
            $this->cacheService->removeCheckData($checkResult->getName());
            return $checkResult;
        }

        $checkResult->setMeta(
            [
                'connection_count' => $connectionCount
            ]
        );

        return $this->processStatus($checkResult, $connectionCount);
    }

    private function processStatus(CheckResultInterface $checkResult, int $connectionCount): CheckResultInterface
    {
        $this->cachedStatusResolver->setStatusTimeThreshold($this->getStatusTimeThreshold());

        if ($connectionCount > $this->getFailedTreshold()) {
            return $this->cachedStatusResolver->updateCacheCheck(
                $checkResult,
                CheckStatus::STATUS_FAILED,
                $connectionCount
            );
        }

        if ($connectionCount > $this->getWarningThreshold()) {
            return $this->cachedStatusResolver->updateCacheCheck(
                $checkResult,
                CheckStatus::STATUS_WARNING,
                $connectionCount
            );
        }

        return $this->cachedStatusResolver->updateCacheCheck(
            $checkResult,
            CheckStatus::STATUS_OK,
            $connectionCount
        );
    }

    private function getStatusTimeThreshold(): int
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'status_time_treshold');
        return is_numeric($configValue) ? (int) $configValue : $this->statusTimeThreshold;
    }

    private function getFailedTreshold(): int
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'failed_treshold');
        return is_numeric($configValue) ? (int) $configValue : $this->failedTreshold;
    }

    private function getWarningThreshold(): int
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'warning_treshold');
        return is_numeric($configValue) ? (int) $configValue : $this->warningThreshold;
    }
}

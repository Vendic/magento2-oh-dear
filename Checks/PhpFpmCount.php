<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Service\CacheService;
use Vendic\OhDear\Utils\BashCommands;
use Vendic\OhDear\Utils\Configuration;
use Vendic\OhDear\Utils\Shell as ShellUtils;
use Vendic\OhDear\Model\CachedStatusResolver;

class PhpFpmCount implements CheckInterface
{
    public function __construct(
        private int $warningThreshold,
        private int $failedTreshold,
        private int $statusTimeThreshold,
        private Configuration $configuration,
        private ShellUtils $shellUtils,
        private CheckResultFactory $checkResultFactory,
        private CachedStatusResolver $cachedStatusResolver,
        private CacheService $cacheService
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('php_fpm_count');
        $checkResult->setLabel('PHP-FPM count');

        if ($this->shellUtils->isMacOS()) {
            $checkResult->setStatus(CheckStatus::STATUS_SKIPPED);
            $checkResult->setShortSummary('This check is not supported on Mac OS');
            $this->cacheService->removeCheckData($checkResult->getName());
            return $checkResult;
        }

        try {
            $processCount = $this->shellUtils->getPhpFpmProcessCount();
        } catch (\Exception $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setNotificationMessage($e->getMessage());
            $checkResult->setShortSummary('Could not get PHP-FPM process count');
            $this->cacheService->removeCheckData($checkResult->getName());
            return $checkResult;
        }

        $checkResult->setMeta(
            [
                'php_fpm_count' => $this->shellUtils->getPhpFpmProcessCount()
            ]
        );

        return $this->processStatus($checkResult, $processCount);
    }

    private function processStatus(CheckResultInterface $checkResult, int $processCount): CheckResultInterface
    {
        $this->cachedStatusResolver->setStatusTimeThreshold($this->getStatusTimeThreshold());

        if ($processCount > $this->getFailedTreshold()) {
            return $this->cachedStatusResolver->updateCacheCheck(
                $checkResult,
                CheckStatus::STATUS_FAILED,
                $processCount
            );
        }

        if ($processCount > $this->getWarningThreshold()) {
            return $this->cachedStatusResolver->updateCacheCheck(
                $checkResult,
                CheckStatus::STATUS_WARNING,
                $processCount
            );
        }

        return $this->cachedStatusResolver->updateCacheCheck(
            $checkResult,
            CheckStatus::STATUS_OK,
            $processCount
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

<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Magento\Framework\Exception\LocalizedException;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Model\CheckResult;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Configuration;
use Vendic\OhDear\Utils\CpuLoad as CpuLoadUtils;
use Vendic\OhDear\Api\Data\CheckStatus;

class CpuLoad implements CheckInterface
{
    public function __construct(
        private float $maxLoadLastMinute,
        private float $maxLoadLastFiveMinutes,
        private float $maxLoadLastFifteenMinutes,
        private Configuration $configuration,
        private CheckResultFactory $checkResultFactory,
        private CpuLoadUtils $cpuLoadUtils,
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('cpu_load');
        $checkResult->setLabel('CPU load');

        try {
            $cpuLoadResults = $this->cpuLoadUtils->measure();
        } catch (LocalizedException $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setNotificationMessage($e->getMessage());
            $checkResult->setShortSummary('Could not get CPU load');
            return $checkResult;
        }

        $checkResult->setMeta(
            [
                'load_last_minute' => $cpuLoadResults->getLoadLastMinute(),
                'load_last_five_minutes' => $cpuLoadResults->getLoadLastFiveMinutes(),
                'load_last_fifteen_minutes' => $cpuLoadResults->getLoadLastFifteenMinutes(),
            ]
        );

        if ($cpuLoadResults->getLoadLastMinute() > $this->getMaxLoadLastMinute()) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setNotificationMessage('CPU load last minute is too high');
            $checkResult->setShortSummary('CPU load last minute is too high');
            return $checkResult;
        }

        if ($cpuLoadResults->getLoadLastFiveMinutes() > $this->getMaxLoadLastFiveMinutes()) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setNotificationMessage('CPU load last five minutes is too high');
            $checkResult->setShortSummary('CPU load last five minutes is too high');
            return $checkResult;
        }

        if ($cpuLoadResults->getLoadLastFifteenMinutes() > $this->getLastFifteenMinutes()) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setNotificationMessage('CPU load last fifteen minutes is too high');
            $checkResult->setShortSummary('CPU load last fifteen minutes is too high');
            return $checkResult;
        }

        $checkResult->setStatus(CheckStatus::STATUS_OK);
        $checkResult->setNotificationMessage('CPU load is OK');
        $checkResult->setShortSummary('CPU load is OK');
        return $checkResult;
    }

    private function getMaxLoadLastMinute(): float
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'max_load_last_minute');
        return is_numeric($configValue) ? (float) $configValue : $this->maxLoadLastMinute;
    }

    private function getMaxLoadLastFiveMinutes(): float
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'max_load_last_five_minutes');
        return is_numeric($configValue) ? (float) $configValue : $this->maxLoadLastFiveMinutes;
    }

    private function getLastFifteenMinutes(): float
    {
        $configValue = $this->configuration->getCheckConfigValue($this, 'max_load_last_fifteen_minutes');
        return is_numeric($configValue) ? (float) $configValue : $this->maxLoadLastFifteenMinutes;
    }
}

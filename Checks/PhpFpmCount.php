<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\BashCommands;
use Vendic\OhDear\Utils\Configuration;
use Vendic\OhDear\Utils\Shell as ShellUtils;

class PhpFpmCount implements CheckInterface
{
    public function __construct(
        private int $warningThreshold,
        private int $failedTreshold,
        private Configuration $configuration,
        private ShellUtils $shellUtils,
        private CheckResultFactory $checkResultFactory,
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
            return $checkResult;
        }

        try {
            $processCount = $this->shellUtils->getPhpFpmProcessCount();
        } catch (\Exception $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setNotificationMessage($e->getMessage());
            $checkResult->setShortSummary('Could not get PHP-FPM process count');
            return $checkResult;
        }

        $checkResult->setMeta(
            [
                'php_fpm_count' => $this->shellUtils->getPhpFpmProcessCount()
            ]
        );

        if ($processCount > $this->getFailedTreshold()) {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setNotificationMessage(
                sprintf(
                    'PHP-FPM process count is too high, (%s)',
                    $processCount
                )
            );
            $checkResult->setShortSummary('PHP-FPM process count error');
            return $checkResult;
        }

        if ($processCount > $this->getWarningThreshold()) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setNotificationMessage(
                sprintf(
                    'PHP-FPM process count is too high, (%s)',
                    $processCount
                )
            );
            $checkResult->setShortSummary('PHP-FPM process count warning');
            return $checkResult;
        }

        $checkResult->setStatus(CheckStatus::STATUS_OK);
        $checkResult->setShortSummary('PHP-FPM process count OK');
        return $checkResult;
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

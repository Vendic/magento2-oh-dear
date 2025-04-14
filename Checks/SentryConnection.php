<?php declare(strict_types=1);

namespace Vendic\OhDear\Checks;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Module\ModuleList;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;

class SentryConnection implements CheckInterface
{
    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private CheckResultFactory $checkResultFactory,
        private ModuleList $moduleList,
        private ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function run(): CheckResultInterface
    {
        $deploymentConfig = $this->deploymentConfig;
        $options = [];
        /** @var CheckResultInterface $checkResult */

        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('sentry_connection');
        $checkResult->setLabel('Sentry connection');
        $checkResult->setMeta($deploymentConfig->get('sentry') ?? []);

        if ($this->moduleList->getOne('JustBetter_Sentry') === null) {
            $checkResult->setStatus(CheckStatus::STATUS_SKIPPED);
            $checkResult->setShortSummary('Sentry module not installed');
            $checkResult->setNotificationMessage('Sentry module not installed');
            return $checkResult;
        }

        if ($this->checkisSentryConfigured($deploymentConfig) === false) {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setShortSummary('Sentry not configured');
            $checkResult->setNotificationMessage('Sentry is not configured');
        } else {
            $checkResult->setStatus(CheckStatus::STATUS_OK);
            $checkResult->setShortSummary('Sentry is configured');
            $checkResult->setNotificationMessage('Sentry is configured');
        }

        return $checkResult;
    }

    private function checkisSentryConfigured(DeploymentConfig $deploymentConfig): bool
    {
        if (
            !empty($deploymentConfig->get('sentry/dsn')) &&
            !empty($deploymentConfig->get('sentry/environment'))
        ) {
            return true;
        }

        if (
            $this->scopeConfig->isSetFlag('sentry/environment/enabled') &&
            !empty($this->scopeConfig->getValue('sentry/environment/dsn'))
        ) {
            return true;
        }

        return false;
    }
}

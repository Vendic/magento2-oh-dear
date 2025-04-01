<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleListInterface;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Configuration;

class TwoFactorAuthentication implements CheckInterface
{
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ModuleListInterface $moduleList,
        private CheckResultFactory $checkResultFactory,
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('two_factor_authentication');
        $checkResult->setLabel('Two Factor Authentication');

        $isTwoFactorAuthModuleEnabled = $this->isModuleEnabled('Magento_TwoFactorAuth');
        $twoFactorAuthSetting = $this->scopeConfig->getValue('twofactorauth/general/enable');

        $checkResult->setMeta(
            [
                'module_enabled' => $isTwoFactorAuthModuleEnabled,
                'setting_value' => $twoFactorAuthSetting
            ]
        );

        if (!$isTwoFactorAuthModuleEnabled) {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setNotificationMessage('Two-Factor Authentication module is not enabled');
            $checkResult->setShortSummary('2FA module is disabled');
            return $checkResult;
        }

        if ($twoFactorAuthSetting == '0') {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setNotificationMessage('Two-Factor Authentication is disabled in configuration');
            $checkResult->setShortSummary('2FA is disabled in config');
            return $checkResult;
        }

        $checkResult->setStatus(CheckStatus::STATUS_OK);
        $checkResult->setNotificationMessage('Two-Factor Authentication is enabled and configured properly');
        $checkResult->setShortSummary('2FA is properly configured');

        return $checkResult;
    }

    /**
     * Check if a module is enabled
     *
     * @param string $moduleName
     * @return bool
     */
    private function isModuleEnabled(string $moduleName): bool
    {
        return $this->moduleList->has($moduleName);
    }
}

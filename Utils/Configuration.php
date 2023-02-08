<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Utils;

use Magento\Framework\App\DeploymentConfig;
use Vendic\OhDear\Api\CheckInterface;

class Configuration
{
    private ?array $ohDearDeploymentConfig = null;

    public function __construct(
        private DeploymentConfig $deploymentConfig
    ) {
    }

    private function getOhdearDeploymentConfig(): array
    {
        if ($this->ohDearDeploymentConfig === null) {
            $this->ohDearDeploymentConfig = $this->deploymentConfig->get('ohdear') ?? [];
        }
        return $this->ohDearDeploymentConfig;
    }

    public function isCheckEnabled(CheckInterface $check): bool
    {
        $checkConfig = $this->getCheckConfig($check);

        if (is_array($checkConfig)) {
            return (bool)($checkConfig['enabled'] ?? true);
        }

        return is_bool($checkConfig) ? $checkConfig : true;
    }

    private function getCheckConfig(CheckInterface $check): bool|null|array
    {
        $className = get_class($check);
        return $this->getOhdearDeploymentConfig()[$className] ?? null;
    }
}

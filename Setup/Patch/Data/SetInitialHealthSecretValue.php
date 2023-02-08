<?php declare(strict_types=1);

namespace Vendic\OhDear\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Vendic\OhDear\Model\Configuration;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
class SetInitialHealthSecretValue implements DataPatchInterface
{
    public function __construct(private WriterInterface $configWriter, private ScopeConfigInterface $scopeConfig)
    {
    }

    public static function getDependencies() : array
    {
        return [];
    }

    public function getAliases() : array
    {
        return [];
    }

    public function apply() : DataPatchInterface
    {
        // Do not set random health if it already exists in the configuration.
        $existingHealthSecret = $this->scopeConfig->getValue(Configuration::HEALTH_SECRET_PATH);
        if (is_string($existingHealthSecret) && strlen($existingHealthSecret) > 0) {
            return $this;
        }

        $randomHealthSecret = uniqid();
        $this->configWriter->save(Configuration::HEALTH_SECRET_PATH, $randomHealthSecret);

        return $this;
    }
}

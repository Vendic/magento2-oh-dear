<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Configuration
{
    public const HEALTH_SECRET_PATH = 'oh_dear/general/health_check_secret';

    public function __construct(private ScopeConfigInterface $scopeConfig)
    {
    }

    public function getOhDearHealthSecret() : string
    {
        return $this->scopeConfig->getValue(self::HEALTH_SECRET_PATH);
    }
}

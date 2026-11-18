<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Configuration
{
    public const HEALTH_SECRET_PATH = 'oh_dear/general/health_check_secret';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private EncryptorInterface $encryptor
    ) {
    }

    public function getOhDearHealthSecret() : string
    {
        $secret = (string) $this->scopeConfig->getValue(self::HEALTH_SECRET_PATH);

        // Saved through the admin the secret is encrypted; secrets set by the data patch or
        // config:set are stored as plain text and are returned as they are.
        if (preg_match('/^\d+:\d+:/', $secret)) {
            return $this->encryptor->decrypt($secret);
        }

        return $secret;
    }
}

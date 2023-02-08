<?php
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Plugin;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Vendic\OhDear\Controller\Index\Index as ApplicationHealthResultController;
use Vendic\OhDear\Model\Configuration;

class ValidateOhDearSecret
{
    public function __construct(
        private HttpRequest $request,
        private JsonFactory $jsonFactory,
        private Configuration $configuration
    ) {
    }

    /**
     * Check if Oh Dear health check secret is valid
     */
    public function aroundExecute(ApplicationHealthResultController $subject, callable $proceed) : Json
    {
        $secret = $this->request->getHeader('oh-dear-health-check-secret');
        $json = $this->jsonFactory->create();

        if (!$secret) {
            return $json->setData('No health secret provided');
        }

        if ($secret !== $this->configuration->getOhDearHealthSecret()) {
            return $json->setData('Invalid health secret provided');
        }

        return $proceed();
    }
}

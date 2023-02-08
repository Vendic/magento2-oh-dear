<?php declare(strict_types=1);

namespace Vendic\OhDear\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Vendic\OhDear\Api\CheckListInterface;
use Vendic\OhDear\Utils\Configuration;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private JsonFactory $jsonFactory,
        private CheckListInterface $checkList,
        private TimezoneInterface $timezone,
        private Configuration $configuration
    ) {
    }

    public function execute() : JsonResult
    {
        $output = [];

        $output['finishedAt'] = $this->now();
        $output['checkResults'] = [];

        foreach ($this->checkList->getChecks() as $check) {
            if ($this->configuration->isCheckEnabled($check) === false) {
                continue;
            }
            $output['checkResults'][] = $check->run()->toArray();
        }

        $json = $this->jsonFactory->create();
        $json->setData($output);

        return $json;
    }

    private function now(): int
    {
        return $this->timezone->date()->getTimestamp();
    }
}

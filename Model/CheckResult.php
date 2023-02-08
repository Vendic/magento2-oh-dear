<?php declare(strict_types=1);

namespace Vendic\OhDear\Model;

use Magento\Framework\DataObject;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
class CheckResult extends DataObject implements CheckResultInterface
{
    public function getName(): string
    {
        return $this->getData(CheckResultInterface::NAME);
    }

    public function setName(string $name): CheckResultInterface
    {
        return $this->setData(CheckResultInterface::NAME, $name);
    }

    public function getLabel(): string
    {
        return $this->getData(CheckResultInterface::LABEL);
    }

    public function setLabel(string $label): CheckResultInterface
    {
        return $this->setData(CheckResultInterface::LABEL, $label);
    }

    public function getNotificationMessage(): string
    {
        return $this->getData(CheckResultInterface::NOTIFICATION_MESSAGE);
    }

    public function setNotificationMessage(string $notificationMessage): CheckResultInterface
    {
        return $this->setData(CheckResultInterface::NOTIFICATION_MESSAGE, $notificationMessage);
    }

    public function getShortSummary(): string
    {
        return $this->getData(CheckResultInterface::SHORT_SUMMARY);
    }

    public function setShortSummary(string $shortSummary): CheckResultInterface
    {
        return $this->setData(CheckResultInterface::SHORT_SUMMARY, $shortSummary);
    }

    public function getStatus(): CheckStatus
    {
        return $this->getData(CheckResultInterface::STATUS);
    }

    public function setStatus(CheckStatus $status): CheckResultInterface
    {
        return $this->setData(CheckResultInterface::STATUS, $status);
    }

    public function getMeta(): array
    {
        return $this->getData(CheckResultInterface::META);
    }

    public function setMeta(array $meta): CheckResultInterface
    {
        return $this->setData(CheckResultInterface::META, $meta);
    }
}

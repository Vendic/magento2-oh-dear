<?php
declare(strict_types=1);

namespace Vendic\OhDear\Api\Data;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
interface CheckResultInterface
{
    public const NAME = 'name';
    public const LABEL = 'label';
    public const NOTIFICATION_MESSAGE = 'notificationMessage';
    public const SHORT_SUMMARY = 'shortSummary';
    public const STATUS = 'status';
    public const META = 'meta';

    public function getName(): string;

    public function setName(string $name): CheckResultInterface;

    public function getLabel(): string;

    public function setLabel(string $label): CheckResultInterface;

    public function getNotificationMessage(): string;

    public function setNotificationMessage(string $notificationMessage): CheckResultInterface;

    public function getShortSummary(): string;

    public function setShortSummary(string $shortSummary): CheckResultInterface;

    public function getStatus(): CheckStatus;

    public function setStatus(CheckStatus $status): CheckResultInterface;

    public function getMeta(): array;

    public function setMeta(array $meta): CheckResultInterface;

    /**
     * @param array $keys
     * @return array
     */
    public function toArray(array $keys = []);
}

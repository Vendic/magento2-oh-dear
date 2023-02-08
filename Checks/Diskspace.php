<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Diskspace as DiskspaceUtils;

class Diskspace implements CheckInterface
{
    public function __construct(
        private CheckResultFactory $checkResultFactory,
        private int $maxPercentageUsed,
        private DiskspaceUtils $diskspaceUtils,
    ) {
    }

    public function run(): CheckResultInterface
    {
        $usedPercentage = $this->diskspaceUtils->getUsedDiskspace() / $this->diskspaceUtils->getTotalDiskSpace() * 100;

        $status = $usedPercentage >= $this->getMaxPercentageUsed() ?
            CheckStatus::STATUS_FAILED :
            CheckStatus::STATUS_OK;

        /** @var CheckResultInterface $checkResult */
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('disk_space');
        $checkResult->setLabel('Disk space');
        $checkResult->setStatus($status);
        $checkResult->setNotificationMessage(
            $status === CheckStatus::STATUS_OK ? 'Disk space is OK' : 'Disk space is running low'
        );
        $checkResult->setShortSummary(sprintf('Disk space is %s%% used', round($usedPercentage, 2)));
        $checkResult->setMeta(
            [
                'used_percentage' => round($usedPercentage, 2),
                'total_disk_space' => $this->diskspaceUtils->getTotalDiskSpace(),
                'free_disk_space' => $this->diskspaceUtils->getFreeDiskspace(),
                'used_disk_space' => $this->diskspaceUtils->getUsedDiskspace(),
            ]
        );

        return $checkResult;
    }

    private function getMaxPercentageUsed(): int
    {
        return $this->maxPercentageUsed;
    }
}

<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Shell as ShellUtils;

class PublicSqlFiles implements CheckInterface
{
    public function __construct(
        private ShellUtils $shellUtils,
        private CheckResultFactory $checkResultFactory
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('public_sql_files');
        $checkResult->setLabel('Public SQL files');

        try {
            $publicSqlFiles = $this->shellUtils->getSqlFilesInPublicRoot();
        } catch (\Exception $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setShortSummary('Public SQL files check crashed');
            $checkResult->setNotificationMessage($e->getMessage());
            return $checkResult;
        }

        $checkResult->setMeta(
            [
                'public_files' => $publicSqlFiles
            ]
        );

        if (count($publicSqlFiles) > 0) {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setShortSummary('Public SQL files found');
            $checkResult->setNotificationMessage(
                sprintf("Public SQL files found: %s", implode(', ', $publicSqlFiles))
            );
            return $checkResult;
        }

        $checkResult->setStatus(CheckStatus::STATUS_OK);
        $checkResult->setShortSummary('No public SQL files found');
        return $checkResult;
    }
}

<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Checks;

use Magento\Framework\App\ResourceConnection;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDear\Utils\Database as DatabseUtils;

class DatabaseConnectionCount implements CheckInterface
{
    public const OK_SUMMARY = 'Database connection count OK';
    public const CONNECTIONS_TO_HIGH_SUMMARY = 'Database connection count is too high';

    public function __construct(
        private CheckResultFactory $checkResultFactory,
        private DatabseUtils $databaseUtils,
        private int $warningThreshold,
        private int $failedTreshold
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('db_connection_count');
        $checkResult->setLabel('DB connection count');

        try {
            $connectionCount = $this->databaseUtils->getConnectionCount();
        } catch (\Exception $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setNotificationMessage($e->getMessage());
            $checkResult->setShortSummary('Could not get database connection count');
            return $checkResult;
        }

        $checkResult->setMeta(
            [
                'connection_count' => $connectionCount
            ]
        );

        if ($connectionCount > $this->failedTreshold) {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setNotificationMessage('Database connection error count is too high');
            $checkResult->setShortSummary(self::CONNECTIONS_TO_HIGH_SUMMARY);
            return $checkResult;
        }

        if ($connectionCount > $this->warningThreshold) {
            $checkResult->setStatus(CheckStatus::STATUS_WARNING);
            $checkResult->setNotificationMessage('Database connection warning: count is too high');
            $checkResult->setShortSummary(self::CONNECTIONS_TO_HIGH_SUMMARY);
            return $checkResult;
        }

        $checkResult->setStatus(CheckStatus::STATUS_OK);
        $checkResult->setShortSummary(self::OK_SUMMARY);
        return $checkResult;
    }
}

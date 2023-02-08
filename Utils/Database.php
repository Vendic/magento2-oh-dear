<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Utils;

use Magento\Framework\App\ResourceConnection;

class Database
{
    public function __construct(
        private ResourceConnection $resourceConnection
    ) {
    }

    public function getConnectionCount(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->query('SHOW FULL PROCESSLIST');
        $results = $query->fetchAll();

        return count($results);
    }
}

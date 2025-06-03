<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\RedisConnection;

class RedisConnectionTest extends TestCase
{
    public function testRedisConnectionWarning(): void
    {
        /** @var RedisConnection $redisConnectionCheck */
        $redisConnectionCheck = Bootstrap::getObjectManager()->get(RedisConnection::class);

        $output = $redisConnectionCheck->run();
        $this->assertEquals('redis_connection', $output->getName());
        $this->assertEquals(
            CheckStatus::STATUS_SKIPPED,
            $output->getStatus(),
            'Redis connection check should be skipped when Redis is not enabled'
        );
    }

    public function testRedisConnectionEnforceRedis(): void
    {
        /** @var RedisConnection $redisConnectionCheck */
        $redisConnectionCheck = Bootstrap::getObjectManager()->create(RedisConnection::class, [
            'enforce_redis' => true,
        ]);

        $output = $redisConnectionCheck->run();
        $this->assertEquals('redis_connection', $output->getName());
        $this->assertEquals(
            CheckStatus::STATUS_FAILED,
            $output->getStatus(),
            'Redis connection check should be skipped when Redis is not enabled'
        );
    }
}

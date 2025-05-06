<?php

declare(strict_types=1);

namespace Vendic\OhDear\Checks;

use Magento\Framework\App\DeploymentConfig;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;

class RabbitmqConnection implements CheckInterface
{
    private string $error = '';

    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private CheckResultFactory $checkResultFactory
    ) {
    }

    public function run(): CheckResultInterface
    {
        $deploymentConfig = $this->deploymentConfig;
        $options = [];
        /** @var CheckResultInterface $checkResult */

        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('rabbitmq_connection');
        $checkResult->setLabel('Rabbitmq Connection');
        $checkResult->setMeta($deploymentConfig->get('queue') ?? []);

        if ($deploymentConfig->get('queue/amqp') === null) {
            $checkResult->setStatus(CheckStatus::STATUS_SKIPPED);
            $checkResult->setShortSummary('Rabbitmq is not enabled');
            $checkResult->setNotificationMessage('Rabbitmq is not enabled');
            return $checkResult;
        }

        if ($this->checkIsRabbitmqConfigured($deploymentConfig) === CheckStatus::STATUS_OK) {
            $checkResult->setStatus(CheckStatus::STATUS_OK);
            $checkResult->setShortSummary('Rabbitmq is correctly configured');
            $checkResult->setNotificationMessage('Rabbitmq is correctly configured');
        } else {
            $checkResult->setStatus(CheckStatus::STATUS_FAILED);
            $checkResult->setShortSummary('Rabbitmq is not configured (correctly)');
            $checkResult->setNotificationMessage('Rabbitmq is not configured (correctly): ' . $this->error);
        }

        return $checkResult;
    }

    private function checkIsRabbitmqConfigured(DeploymentConfig $deploymentConfig): CheckStatus
    {
        $host = $deploymentConfig->get('queue/amqp/host');
        $port = $deploymentConfig->get('queue/amqp/port');
        $user = $deploymentConfig->get('queue/amqp/user');
        $password = $deploymentConfig->get('queue/amqp/password');
        $vhost = $deploymentConfig->get('queue/amqp/virtualhost');
        $connectionTimeout = 0.01337;

        if (
            $deploymentConfig->get('queue')
            && !empty($deploymentConfig->get('queue/amqp'))
            && $host
            && $port
            && $user
            && $password
        ) {
            try {
                $connection = new AMQPStreamConnection(
                    host: $host,
                    port: $port,
                    user: $user,
                    password: $password,
                    vhost: $vhost,
                    connection_timeout: $connectionTimeout
                );
                if ($connection->isConnected()) {
                    return CheckStatus::STATUS_OK;
                } else {
                    $this->error = 'Error unknown';
                    return CheckStatus::STATUS_FAILED;
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'after ' . $connectionTimeout . ' sec')) {
                    return CheckStatus::STATUS_OK;
                }
                $this->error = $e->getMessage();
                return CheckStatus::STATUS_FAILED;
            }
        }

        return CheckStatus::STATUS_FAILED;
    }
}

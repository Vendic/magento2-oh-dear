<?php
declare(strict_types=1);

namespace Vendic\OhDear\Checks;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Magento\Framework\App\RequestInterface;

class ServernameAccount implements CheckInterface
{
    public function __construct(
        private CheckResultFactory $checkResultFactory,
        private RequestInterface $request
    ) {
    }

    public function run(): CheckResultInterface
    {
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('server_name_and_user');
        $checkResult->setLabel('Server name and user');

        $checkResult->setMeta(
            [
                'hostname' => gethostname(),
                'user' => get_current_user(),
                'server_port' => $this->getServerPort(),
                'protocol' => $this->getServerProtocol()
            ]
        );

        $checkResult->setShortSummary(get_current_user() . '@' . gethostname());
        $checkResult->setStatus(CheckStatus::STATUS_SKIPPED);
        return $checkResult;
    }

    protected function getServerPort(): string
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->request;
        return (string)$request->getServer()->get('SERVER_PORT');
    }

    protected function getServerProtocol(): string
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->request;
        return (string)$request->getServer()->get('SERVER_PROTOCOL');
    }
}

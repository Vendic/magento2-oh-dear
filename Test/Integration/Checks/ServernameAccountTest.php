<?php
declare(strict_types=1);

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Checks\ServernameAccount;
use Vendic\OhDear\Model\CheckResultFactory;

class ServernameAccountTest extends TestCase
{
    public function testServernameAndAccountCheck()
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var MockObject & ServernameAccount $servernameAccountCheckMock */
        $servernameAccountCheckMock = $this->getMockBuilder(ServernameAccount::class)
            ->setConstructorArgs(
                [
                    $objectManager->get(CheckResultFactory::class),
                    $objectManager->get(RequestInterface::class)
                ]
            )
            ->onlyMethods(['getServerPort', 'getServerProtocol'])
            ->getMock();

        $servernameAccountCheckMock->method('getServerPort')->willReturn('443');
        $servernameAccountCheckMock->method('getServerProtocol')->willReturn('HTTP/1.1');
        $checkResult = $servernameAccountCheckMock->run();

        $this->assertEquals('server_name_and_user', $checkResult->getName());
        $this->assertEquals('Server name and user', $checkResult->getLabel());
        $this->assertEquals(
            [
                'hostname' => gethostname(),
                'user' => get_current_user(),
                'server_port' => '443',
                'protocol' => 'HTTP/1.1'
            ],
            $checkResult->getMeta()
        );
        $this->assertEquals(get_current_user() . '@' . gethostname(), $checkResult->getShortSummary());
    }
}

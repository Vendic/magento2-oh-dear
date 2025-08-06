<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Magento\Framework\Module\ModuleList;
use Magento\Framework\Module\ModuleListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Checks\TwoFactorAuthentication;

/**
 * @magentoAppIsolation enabled
 */
class TwoFactorAuthenticationTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ConfigFixture
     */
    private $configFixture;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->configFixture = new ConfigFixture();
    }

    protected function tearDown(): void
    {
        $this->configFixture = null;
    }

    /**
     * @dataProvider twoFactorAuthConfigDataProvider
     */
    public function testTwoFactorAuthWithConfig(
        string $configValue,
        CheckStatus $expectedStatus,
        string $expectedShortSummary,
        string $message = ''
    ): void {
        // Mock the module list to always return true for TFA module
        $moduleListMock = $this->getModuleListMock(true);
        
        // Clear the ObjectManager cache to ensure fresh instances
        $this->objectManager->clearCache();
        
        $this->objectManager->addSharedInstance($moduleListMock, ModuleListInterface::class);

        // Set up config value using TddWizard fixture
        ConfigFixture::setGlobal('twofactorauth/general/enable', $configValue);

        /** @var TwoFactorAuthentication $twoFactorAuthCheck */
        $twoFactorAuthCheck = $this->objectManager->get(TwoFactorAuthentication::class);
        $checkResult = $twoFactorAuthCheck->run();

        $this->assertEquals('two_factor_authentication', $checkResult->getName());
        $this->assertEquals($expectedStatus, $checkResult->getStatus(), $message);
        $this->assertEquals($expectedShortSummary, $checkResult->getShortSummary());
    }

    public static function twoFactorAuthConfigDataProvider(): array
    {
        return [
            [
                '1', CheckStatus::STATUS_OK,
                '2FA is properly configured',
                'Module enabled and config enabled: OK'
            ],
            [
                '0', CheckStatus::STATUS_FAILED,
                '2FA is disabled in config',
                'Module enabled but config disabled: FAILED'
            ],
        ];
    }

    /**
     * Tests behavior when module is disabled (regardless of config)
     */
    public function testDisabledModule(): void
    {
        // Mock the module list to return false for TFA module
        $moduleListMock = $this->getModuleListMock(false);
        
        // Clear the ObjectManager cache to ensure fresh instances
        $this->objectManager->clearCache();
        
        $this->objectManager->addSharedInstance($moduleListMock, ModuleList::class);

        // Config value doesn't matter for this test, but set it anyway
        ConfigFixture::setGlobal('twofactorauth/general/enable', '1');

        /** @var TwoFactorAuthentication $twoFactorAuthCheck */
        $twoFactorAuthCheck = $this->objectManager->get(TwoFactorAuthentication::class);
        $checkResult = $twoFactorAuthCheck->run();

        $this->assertEquals('two_factor_authentication', $checkResult->getName());
        $this->assertEquals(CheckStatus::STATUS_FAILED, $checkResult->getStatus());
        $this->assertEquals('2FA module is disabled', $checkResult->getShortSummary());
    }

    /**
     * @return ModuleListInterface&MockObject
     */
    private function getModuleListMock(bool $isModuleEnabled): ModuleListInterface&MockObject
    {
        /** @var ModuleListInterface & MockObject $moduleListMock */
        $moduleListMock = $this->getMockBuilder(ModuleListInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['has', 'getAll', 'getOne', 'getNames'])
            ->getMock();

        $moduleListMock->method('has')
            ->with('Magento_TwoFactorAuth')
            ->willReturn($isModuleEnabled);

        return $moduleListMock;
    }
}

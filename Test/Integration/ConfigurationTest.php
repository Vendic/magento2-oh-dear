<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration;

use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Model\Configuration;

class ConfigurationTest extends TestCase
{
    private const SECRET = 'plain-secret-value';

    public function testGetOhDearHealthSecret(): void
    {
        /** @var Configuration $configuration */
        $configuration = Bootstrap::getObjectManager()->get(Configuration::class);
        $this->assertMatchesRegularExpression(
            '/\w{13}/',
            $configuration->getOhDearHealthSecret(),
            'Health secret should be a string of 13 characters'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testPlainTextSecretIsReturnedAsItIs(): void
    {
        $this->writeSecret(self::SECRET);

        $this->assertSame(self::SECRET, $this->getConfiguration()->getOhDearHealthSecret());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testEncryptedSecretIsDecrypted(): void
    {
        /** @var EncryptorInterface $encryptor */
        $encryptor = Bootstrap::getObjectManager()->get(EncryptorInterface::class);
        $this->writeSecret($encryptor->encrypt(self::SECRET));

        $this->assertSame(self::SECRET, $this->getConfiguration()->getOhDearHealthSecret());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testSecretIsEncryptedWhenSavedInTheAdmin(): void
    {
        $this->saveSecretInAdmin(self::SECRET);

        $this->assertNotSame(self::SECRET, $this->getStoredSecret(), 'The stored secret should be encrypted');
        $this->assertSame(self::SECRET, $this->getConfiguration()->getOhDearHealthSecret());
    }

    /**
     * The obscure field posts six asterisks when the section is saved without changing the secret.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testSecretIsKeptWhenSavedUnchangedInTheAdmin(): void
    {
        $this->saveSecretInAdmin(self::SECRET);
        $this->saveSecretInAdmin('******');

        $this->assertSame(self::SECRET, $this->getConfiguration()->getOhDearHealthSecret());
    }

    private function writeSecret(string $value): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->get(WriterInterface::class)->save(Configuration::HEALTH_SECRET_PATH, $value);
        $objectManager->get(ReinitableConfigInterface::class)->reinit();
    }

    private function saveSecretInAdmin(string $value): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Config $config */
        $config = $objectManager->get(ConfigFactory::class)->create([
            'data' => [
                'section' => 'oh_dear',
                'website' => null,
                'store' => null,
                'groups' => [
                    'general' => [
                        'fields' => [
                            'health_check_secret' => ['value' => $value],
                        ],
                    ],
                ],
            ],
        ]);
        $config->save();
        $objectManager->get(ReinitableConfigInterface::class)->reinit();
    }

    private function getStoredSecret(): string
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from($resource->getTableName('core_config_data'), ['value'])
            ->where('path = ?', Configuration::HEALTH_SECRET_PATH)
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0);

        return (string) $connection->fetchOne($select);
    }

    private function getConfiguration(): Configuration
    {
        return Bootstrap::getObjectManager()->create(Configuration::class);
    }
}

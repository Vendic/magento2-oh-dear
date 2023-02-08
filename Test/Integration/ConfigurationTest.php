<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Model\Configuration;

class ConfigurationTest extends TestCase
{
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
}

<?php declare(strict_types=1);

namespace Vendic\OhDear\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\CheckListInterface;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
class CheckListTest extends TestCase
{
    public function testGetAndRunChecks() : void
    {
        /** @var CheckListInterface $checkList */
        $checkList = Bootstrap::getObjectManager()->get(CheckListInterface::class);
        $checks = $checkList->getChecks();

        $this->assertGreaterThanOrEqual(1, count($checks));
        foreach ($checks as $check) {
            $this->assertInstanceOf(CheckInterface::class, $check);
            // Run check, it should not trigger any exceptions
            $check->run();
        }
    }
}

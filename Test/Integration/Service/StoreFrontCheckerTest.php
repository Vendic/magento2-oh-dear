<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Service;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Model\StoreFronts\HttpStatusFetcher;
use Vendic\OhDear\Service\StoreFrontChecker;

class StoreFrontCheckerTest extends TestCase
{
    public function testReportsNoFailuresWhenAllStoreFrontsReturn200(): void
    {
        $result = $this->check(
            [
                'https://store1.example.com/' => ['status' => 200, 'error' => null],
                'https://store2.example.com/' => ['status' => 200, 'error' => null],
            ]
        );

        $this->assertSame([], $result['failed']);
        $this->assertSame(2, $result['checked_count']);
        $this->assertEqualsWithDelta(time(), $result['checked_at'], 10);
    }

    public function testReportsNon200ResponsesAndConnectionErrorsAsFailedDomains(): void
    {
        $result = $this->check(
            [
                'https://store1.example.com/' => ['status' => 200, 'error' => null],
                'https://store2.example.com/' => ['status' => 500, 'error' => null],
                'https://store3.example.com/' => ['status' => 301, 'error' => null],
                'https://store4.example.com/' => ['status' => 0, 'error' => 'Connection timed out'],
            ]
        );

        $this->assertSame(
            [
                'store2.example.com' => 'HTTP 500',
                'store3.example.com' => 'HTTP 301',
                'store4.example.com' => 'Connection timed out',
            ],
            $result['failed'],
            'Only domains that did not end in an HTTP 200 should be reported, keyed by domain'
        );
        $this->assertSame(4, $result['checked_count']);
    }

    /**
     * @param array<string, array{status: int, error: ?string}> $fetcherResults
     * @return array{checked_at: int, checked_count: int, failed: array<string, string>}
     */
    private function check(array $fetcherResults): array
    {
        $fetcher = $this->createMock(HttpStatusFetcher::class);
        $fetcher->method('fetch')->willReturn($fetcherResults);

        /** @var StoreFrontChecker $checker */
        $checker = Bootstrap::getObjectManager()->create(
            StoreFrontChecker::class,
            ['httpStatusFetcher' => $fetcher]
        );

        return $checker->check(array_keys($fetcherResults));
    }
}

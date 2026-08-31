<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Model\StoreFronts;

use Magento\Framework\HTTP\AsyncClient\HttpException;
use Magento\Framework\HTTP\AsyncClient\HttpResponseDeferredInterface;
use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\HTTP\AsyncClient\Response;
use Magento\Framework\HTTP\AsyncClientInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Model\StoreFronts\HttpStatusFetcher;

class HttpStatusFetcherTest extends TestCase
{
    public function testReturnsTheHttpStatusPerUrl(): void
    {
        $results = $this->fetch(
            [
                'https://store1.example.com/' => fn (): Response => new Response(200, [], ''),
                'https://store2.example.com/' => fn (): Response => new Response(503, [], ''),
            ]
        );

        $this->assertSame(
            [
                'https://store1.example.com/' => ['status' => 200, 'error' => null],
                'https://store2.example.com/' => ['status' => 503, 'error' => null],
            ],
            $results
        );
    }

    public function testReturnsStatusZeroAndTheErrorMessageWhenTheRequestFails(): void
    {
        $results = $this->fetch(
            [
                'https://store1.example.com/' => function (): Response {
                    throw new HttpException('Connection timed out');
                },
            ]
        );

        $this->assertSame(
            ['https://store1.example.com/' => ['status' => 0, 'error' => 'Connection timed out']],
            $results
        );
    }

    /**
     * @param array<string, callable(): Response> $responsesByUrl
     * @return array<string, array{status: int, error: string|null}>
     */
    private function fetch(array $responsesByUrl): array
    {
        $asyncClient = $this->createMock(AsyncClientInterface::class);
        $asyncClient
            ->expects($this->exactly(count($responsesByUrl)))
            ->method('request')
            ->willReturnCallback(function (Request $request) use ($responsesByUrl) {
                $this->assertArrayHasKey($request->getUrl(), $responsesByUrl);
                $this->assertSame(Request::METHOD_GET, $request->getMethod());

                $deferred = $this->createMock(HttpResponseDeferredInterface::class);
                $deferred->method('get')->willReturnCallback($responsesByUrl[$request->getUrl()]);

                return $deferred;
            });

        /** @var HttpStatusFetcher $fetcher */
        $fetcher = Bootstrap::getObjectManager()->create(
            HttpStatusFetcher::class,
            ['asyncClient' => $asyncClient]
        );

        return $fetcher->fetch(array_keys($responsesByUrl));
    }
}

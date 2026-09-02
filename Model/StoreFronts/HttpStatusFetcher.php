<?php

declare(strict_types=1);

namespace Vendic\OhDear\Model\StoreFronts;

use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\HTTP\AsyncClientInterface;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 *
 * Fetches the final HTTP status of multiple URLs in parallel: all requests are
 * fired before the first deferred response is awaited, so they run concurrently.
 * Timeouts and redirect behaviour are configured on the injected async client
 * (see the virtual types in etc/di.xml).
 */
class HttpStatusFetcher
{
    public function __construct(
        private AsyncClientInterface $asyncClient,
        private string $userAgent = 'Vendic-OhDear-StoreFrontCheck/1.0'
    ) {
    }

    /**
     * Returns ['status' => int, 'error' => string|null] per URL: the final HTTP status,
     * or status 0 with an error message when the request could not complete.
     *
     * @param string[] $urls
     */
    public function fetch(array $urls): array
    {
        $deferredResponses = [];
        foreach ($urls as $url) {
            $deferredResponses[$url] = $this->asyncClient->request(
                new Request($url, Request::METHOD_GET, ['User-Agent' => $this->userAgent], null)
            );
        }

        $results = [];
        foreach ($deferredResponses as $url => $deferredResponse) {
            try {
                $results[$url] = [
                    'status' => $deferredResponse->get()->getStatusCode(),
                    'error' => null,
                ];
            } catch (\Throwable $exception) {
                $results[$url] = [
                    'status' => 0,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }
}

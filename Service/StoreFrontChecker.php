<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\OhDear\Service;

use Vendic\OhDear\Model\StoreFronts\HttpStatusFetcher;

class StoreFrontChecker
{
    public function __construct(private HttpStatusFetcher $httpStatusFetcher)
    {
    }

    /**
     * @param string[] $urls
     */
    public function check(array $urls): array
    {
        $failed = [];
        $results = $this->httpStatusFetcher->fetch($urls);

        foreach ($results as $url => $result) {
            $status = $result['status'];
            $error = $result['error'];

            if ($status === 200 && $error === null) {
                continue;
            }

            $failed[$url] = $error ?? sprintf('HTTP %d', $status);
        }

        return [
            'checked_at' => time(),
            'checked_count' => count($results),
            'failed' => $failed,
        ];
    }
}

<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Model;

use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\CheckListInterface;

class CheckList implements CheckListInterface
{
    public function __construct(private readonly array $checks)
    {
    }

    /**
     * @return CheckInterface[]
     */
    public function getChecks(): array
    {
        return array_filter($this->checks, function ($check) {
            return $check instanceof CheckInterface;
        });
    }
}

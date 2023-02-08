<?php
declare(strict_types=1);

namespace Vendic\OhDear\Api;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
interface CheckListInterface
{
    /**
     * @return CheckInterface[]
     */
    public function getChecks(): array;
}

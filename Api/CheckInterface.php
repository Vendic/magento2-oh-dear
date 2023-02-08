<?php
declare(strict_types=1);

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Api;

use Vendic\OhDear\Api\Data\CheckResultInterface;

interface CheckInterface
{
    public function run(): CheckResultInterface;
}

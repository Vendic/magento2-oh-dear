<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Api\Data;

enum CheckStatus: string
{
    case STATUS_OK = 'ok';
    case STATUS_WARNING = 'warning';
    case STATUS_FAILED = 'failed';
    case STATUS_CRASHED = 'crashed';
    case STATUS_SKIPPED = 'skipped';
}

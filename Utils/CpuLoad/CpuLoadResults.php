<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Utils\CpuLoad;

use Magento\Framework\DataObject;

class CpuLoadResults extends DataObject
{
    public function getLoadLastMinute(): float
    {
        return (float) $this->getData('load_last_minute');
    }

    public function getLoadLastFiveMinutes(): float
    {
        return (float) $this->getData('load_last_five_minutes');
    }

    public function getLoadLastFifteenMinutes(): float
    {
        return (float) $this->getData('load_last_fifteen_minutes');
    }
}

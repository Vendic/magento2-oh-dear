<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Utils\CpuLoad;

use Magento\Framework\DataObject;

class CpuLoadResults extends DataObject
{
    public function getLoadLastMinute(): float|int
    {
        return (float) $this->getData('load_last_minute');
    }

    public function getLoadLastFiveMinutes(): float|int
    {
        return (float) $this->getData('load_last_five_minutes');
    }

    public function getLoadLastFifteenMinutes(): float|int
    {
        return (float) $this->getData('load_last_fifteen_minutes');
    }
}

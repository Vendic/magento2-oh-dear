<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Utils;

use Magento\Framework\Exception\LocalizedException;
use Vendic\OhDear\Utils\CpuLoad\CpuLoadResults;
use Vendic\OhDear\Utils\CpuLoad\CpuLoadResultsFactory;

class CpuLoad
{
    public function __construct(
        private CpuLoadResultsFactory $cpuLoadResultsFactory
    ) {
    }

    public function measure(): CpuLoadResults
    {
        $result = false;

        if (function_exists('sys_getloadavg')) {
            $result = sys_getloadavg();
        }

        if (!$result) {
            throw new LocalizedException(__('Could not get CPU load'));
        }

        /** @var CpuLoadResults $cpuLoadResult */
        $cpuLoadResult = $this->cpuLoadResultsFactory->create(
            [
                'data' => [
                    'load_last_minute' => $result[0],
                    'load_last_five_minutes' => $result[1],
                    'load_last_fifteen_minutes' => $result[2],
                ]
            ]
        );
        return $cpuLoadResult;
    }
}

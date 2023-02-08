<?php declare(strict_types=1);

namespace Vendic\OhDear\Utils;

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
class Diskspace
{
    public function getFreeDiskspace() : int
    {
        return (int) disk_free_space('/');
    }

    public function getUsedDiskspace() : int
    {
        $total = $this->getTotalDiskSpace();
        $free = $this->getFreeDiskspace();
        $used = $total - $free;

        return (int) $used;
    }

    public function getTotalDiskSpace() : int
    {
        return (int) disk_total_space('/');
    }
}

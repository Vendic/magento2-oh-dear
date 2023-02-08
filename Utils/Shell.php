<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Utils;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Shell
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function getPhpFpmProcessCount(): int
    {
        if ($this->isMacOS()) {
            throw new LocalizedException(__('This method is not supported on Mac OS'));
        }

        $result = $this->runCommand('ps fx | grep "php-fpm" | grep -v grep | wc -l');
        if (!is_numeric($result)) {
            throw new LocalizedException(__('Could not get PHP-FPM process count'));
        }

        return (int)$result;
    }

    public function getSqlFilesInPublicRoot() : array
    {
        $pubFolder = $this->filesystem->getDirectoryRead(DirectoryList::PUB)->getAbsolutePath();
        $command = sprintf('find %s  -maxdepth 2 -type f -name "*.sql*"', $pubFolder);
        $result = $this->runCommand($command);

        if (!is_string($result)) {
            throw new LocalizedException(__('Cannot execute command %1', $command));
        }

        $count = explode(PHP_EOL, $result);
        return array_filter($count);
    }

    public function runCommand(string $string): mixed
    {
        $process = Process::fromShellCommandline($string);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    public function isMacOS(): bool
    {
        $operatingSystem = php_uname();
        return str_contains($operatingSystem, 'Darwin');
    }
}

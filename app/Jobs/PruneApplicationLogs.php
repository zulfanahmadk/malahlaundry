<?php

namespace App\Jobs;

use Carbon\CarbonImmutable;

class PruneApplicationLogs
{
    /** Keep today and the previous two calendar days in WIB. */
    public function handle(?string $directory = null, bool $dryRun = false): int
    {
        $directory ??= storage_path('logs');
        $cutoff = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDays(2);
        $removed = 0;
        if (! is_dir($directory)) {
            return 0;
        }

        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isLink() || ! $file->isFile() || $file->getExtension() !== 'log') {
                continue;
            }
            $date = CarbonImmutable::createFromTimestamp($file->getMTime(), 'Asia/Jakarta');
            if (preg_match('/-(\d{4}-\d{2}-\d{2})\.log$/', $file->getFilename(), $match)) {
                try {
                    $date = CarbonImmutable::createFromFormat('!Y-m-d', $match[1], 'Asia/Jakarta');
                } catch (\Throwable) {
                    continue;
                }
            }
            if ($date->lt($cutoff) && ($dryRun || unlink($file->getPathname()))) {
                $removed++;
            }
        }

        return $removed;
    }
}

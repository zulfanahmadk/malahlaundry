<?php

namespace App\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;

class PruneAttendancePhotos
{
    /** Keep the current calendar month and the previous month in WIB. */
    public function handle(?string $directory = null, bool $dryRun = false): array
    {
        $directory ??= storage_path('app/public/attendances');
        $root = realpath($directory);
        if ($root === false || ! is_dir($root)) {
            return [];
        }
        $cutoff = CarbonImmutable::now('Asia/Jakarta')->startOfMonth()->subMonth()->format('Y-m');
        $removed = [];
        $filesystem = new Filesystem();

        foreach (new \DirectoryIterator($root) as $year) {
            if ($year->isLink() || ! $year->isDir() || ! preg_match('/^\d{4}$/', $year->getFilename())) {
                continue;
            }
            foreach (new \DirectoryIterator($year->getPathname()) as $month) {
                if ($month->isLink() || ! $month->isDir() || ! preg_match('/^(0[1-9]|1[0-2])$/', $month->getFilename())) {
                    continue;
                }
                if ($year->getFilename().'-'.$month->getFilename() >= $cutoff) {
                    continue;
                }
                $target = $month->getRealPath();
                // Verify the canonical target stays within the attendance photo root before recursive deletion.
                if ($target === false || ! str_starts_with($target, $root.DIRECTORY_SEPARATOR)) {
                    continue;
                }
                if (! $dryRun && ! $filesystem->deleteDirectory($target)) {
                    throw new \RuntimeException('Folder foto gagal dihapus: '.$target);
                }
                $removed[] = $year->getFilename().'/'.$month->getFilename();
            }
            if (! $dryRun && ! (new \FilesystemIterator($year->getPathname()))->valid()) {
                // rmdir only removes an empty year; it never recursively removes unexpected entries.
                @rmdir($year->getPathname());
            }
        }

        return $removed;
    }
}

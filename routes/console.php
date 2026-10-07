<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('logs:prune {--dry-run : Count files without deleting}', function () {
    $count = app(\App\Jobs\PruneApplicationLogs::class)->handle(dryRun: (bool) $this->option('dry-run'));
    $this->info($count.' file log '.($this->option('dry-run') ? 'akan dihapus.' : 'dihapus.'));
})->purpose('Keep only the last three calendar days of application log files');

Artisan::command('photos:prune {--dry-run : List old month folders without deleting}', function () {
    $folders = app(\App\Jobs\PruneAttendancePhotos::class)->handle(dryRun: (bool) $this->option('dry-run'));
    foreach ($folders as $folder) {
        $this->line($folder);
    }
    $this->info(count($folders).' folder bulan '.($this->option('dry-run') ? 'akan dihapus.' : 'dihapus.'));
})->purpose('Keep attendance photos for the current and previous calendar month');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

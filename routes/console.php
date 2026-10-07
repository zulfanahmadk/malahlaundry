<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('logs:prune {--dry-run : Count files without deleting}', function () {
    $count = app(\App\Jobs\PruneApplicationLogs::class)->handle(dryRun: (bool) $this->option('dry-run'));
    $this->info($count.' file log '.($this->option('dry-run') ? 'akan dihapus.' : 'dihapus.'));
})->purpose('Keep only the last three calendar days of application log files');

Schedule::command('logs:prune')->dailyAt('00:15')->timezone('Asia/Jakarta')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

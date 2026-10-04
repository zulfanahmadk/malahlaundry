<?php

namespace App\Services;

use App\Models\Branch;
use Carbon\Carbon;

class BranchHours
{
    public function schedule(Branch $branch, Carbon $time): ?array
    {
        $time = $time->copy()->timezone('Asia/Jakarta');
        if ($branch->operational_preferences['use_exceptions'] ?? true) {
            $exception = collect($branch->opening_exceptions ?? [])->firstWhere('date', $time->toDateString());
            if ($exception) return $exception;
        }
        return collect($branch->opening_hours ?? [])->firstWhere('day', $time->dayOfWeekIso);
    }

    public function status(Branch $branch): string
    {
        if (! $branch->active) return 'Cabang nonaktif';
        $time = now('Asia/Jakarta');
        $hours = $this->schedule($branch, $time);
        if (! $hours) return 'Jam buka belum diatur';
        if (! $hours['open']) return 'Tutup hari ini';
        return $time->format('H:i') >= $hours['from'] && $time->format('H:i') < $hours['to'] ? 'Sedang buka' : 'Di luar jam buka';
    }

    public function attendance(Branch $branch, Carbon $checkIn): string
    {
        $hours = $this->schedule($branch, $checkIn);
        if (! $hours || ! $hours['open']) return 'TERCATAT';
        $start = $checkIn->copy()->timezone('Asia/Jakarta')->startOfDay()->setTimeFromTimeString($hours['from']);
        return $checkIn->gt($start) ? 'TERLAMBAT' : 'TEPAT WAKTU';
    }
}

<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use Illuminate\Support\Str;

class AttendancePhotoPath
{
    public function directory(Attendance $attendance): string
    {
        $branch = Branch::find($attendance->branch_id);
        $date = ($attendance->check_in_time ?? now())->copy()->timezone('Asia/Jakarta');
        $username = Str::slug($attendance->user?->username ?? '') ?: 'user-'.$attendance->user_id;
        $branchName = Str::slug($branch?->name ?? '') ?: 'cabang-'.$attendance->branch_id;

        return 'attendances/'.$date->format('Y/m').'/'.$username.'/'.$branchName;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToBranch;

    protected $table = 'attendances';

    protected $fillable = [
        'branch_id', 'in_latitude', 'in_longitude', 'in_accuracy', 'out_latitude', 'out_longitude', 'out_accuracy',
        'uuid',
        'user_id',
        'device_id',
        'check_in_time',
        'check_in_photo_path',
        'check_out_time',
        'check_out_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($attendance) {
            if (empty($attendance->uuid)) {
                $attendance->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

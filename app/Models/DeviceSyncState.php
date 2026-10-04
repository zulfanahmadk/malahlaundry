<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSyncState extends Model
{
    use \App\Models\Concerns\BelongsToBranch;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'pending_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getAttentionAttribute(): bool
    {
        return $this->failed_count > 0
            || filled($this->last_error)
            || $this->last_synced_at === null
            || $this->last_seen_at->lt(now()->subMinutes(30));
    }

    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->last_seen_at->lt(now()->subMinutes(30)) => 'BELUM MELAPOR',
            $this->failed_count > 0 || filled($this->last_error) => 'GAGAL',
            $this->pending_count > 0 => 'TERTUNDA',
            $this->last_synced_at === null => 'BELUM SELESAI SINKRON',
            default => 'TANPA ANTREAN',
        };
    }
}

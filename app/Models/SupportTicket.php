<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const TYPES = ['BUGS' => 'Bugs / Keluhan', 'ENCH' => 'Enhancement'];
    public const STATUSES = [
        'SUBMITTED' => 'Diajukan', 'ACCEPTED' => 'Diterima', 'REJECTED' => 'Ditolak',
        'IN_PROGRESS' => 'Dikerjakan', 'RESOLVED' => 'Selesai',
    ];
    public const PLATFORMS = ['WEB' => 'Website', 'ANDROID' => 'Android', 'OTHER' => 'Lainnya'];
    public const TRANSITIONS = [
        'SUBMITTED' => ['ACCEPTED', 'REJECTED'],
        'ACCEPTED' => ['IN_PROGRESS', 'RESOLVED', 'REJECTED'],
        'IN_PROGRESS' => ['RESOLVED'],
        'REJECTED' => [], 'RESOLVED' => [],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['submitted_by' => 'integer', 'branch_id' => 'integer', 'progress' => 'integer', 'revision' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(TicketUpdate::class)->orderBy('id');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['REJECTED', 'RESOLVED'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status];
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'RESOLVED' => 'badge-success', 'REJECTED' => 'badge-danger',
            'IN_PROGRESS' => 'badge-teal', 'ACCEPTED' => 'badge-primary', default => 'badge-warning',
        };
    }
}

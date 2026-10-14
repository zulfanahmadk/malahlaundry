<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramDelivery extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer', 'available_at' => 'datetime',
            'queued_at' => 'datetime', 'sent_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TelegramSubscription::class);
    }
}

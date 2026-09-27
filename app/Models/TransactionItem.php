<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    protected $table = 'transaction_items';

    protected $fillable = [
        'transaction_uuid',
        'service_uuid',
        'qty',
        'price',
        'service_name',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'float',
            'price' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_uuid', 'uuid');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_uuid', 'uuid');
    }

    public function getSubtotalAttribute(): int
    {
        return (int) round($this->qty * $this->price);
    }
}

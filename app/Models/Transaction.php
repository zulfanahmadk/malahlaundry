<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'customer_uuid',
        'user_id',
        'transaction_number',
        'subtotal',
        'total',
        'payment_status',
        'laundry_status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'total' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
            if (empty($transaction->transaction_number)) {
                $dateStr = now()->format('Ymd');
                $countToday = static::whereDate('created_at', today())->count() + 1;
                $transaction->transaction_number = 'TRX-' . $dateStr . '-' . str_pad($countToday, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_uuid', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_uuid', 'uuid');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'LUNAS';
    }

    public function getPublicReceiptUrlAttribute(): string
    {
        return url('/n/' . $this->uuid);
    }
}

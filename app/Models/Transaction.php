<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToBranch;

    protected $table = 'transactions';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id', 'estimated_at', 'ready_at', 'paid_at', 'payment_method', 'version',
        'uuid',
        'customer_uuid',
        'user_id',
        'transaction_number',
        'subtotal',
        'total',
        'payment_status',
        'laundry_status',
        'picked_up_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_at' => 'datetime', 'ready_at' => 'datetime', 'paid_at' => 'datetime', 'version' => 'integer',
            'subtotal' => 'integer',
            'total' => 'integer',
            'picked_up_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
            if (empty($transaction->transaction_number)) {
                $time = ($transaction->created_at ?? now())->copy()->setTimezone('Asia/Jakarta');
                do {
                    $number = 'KL'.$time->format('ymdHisv');
                    $time->addMillisecond();
                } while (static::withoutGlobalScope('branch')->where('transaction_number', $number)->exists());
                $transaction->transaction_number = $number;
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_uuid', 'uuid');
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        if (isset($filters['q']) && $filters['q'] !== '') {
            $search = '%'.$filters['q'].'%';
            $query->where(function (Builder $query) use ($search) {
                $query->where('transaction_number', 'like', $search)
                    ->orWhereHas('customer', function (Builder $customer) use ($search) {
                        $customer->where('name', 'like', $search)->orWhere('phone', 'like', $search);
                    });
            });
        }

        foreach (['status' => 'laundry_status', 'payment' => 'payment_status'] as $key => $column) {
            if (!empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
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
        $base = config('domains.receipt_url');

        return $base ? rtrim($base, '/').'/n/'.$this->uuid : url('/n/'.$this->uuid);
    }
}

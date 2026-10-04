<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToBranch;

    protected $table = 'customers';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id', 'notes', 'archived_at',
        'uuid',
        'name',
        'phone',
        'address',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function ($customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_uuid', 'uuid');
    }
}

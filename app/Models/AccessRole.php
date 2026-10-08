<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRole extends Model
{
    protected $fillable = ['branch_id', 'name', 'base_role', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }
}

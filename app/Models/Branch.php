<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'show_branch' => 'boolean', 'opening_hours' => 'array', 'templates' => 'array'];
    }
}

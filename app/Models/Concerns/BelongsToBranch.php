<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            $branch = request()->attributes->get('branch_id');
            if ($branch !== null) {
                $query->where($query->getModel()->qualifyColumn('branch_id'), $branch);
            }
        });
        static::creating(function ($record) {
            $record->branch_id ??= request()->attributes->get('branch_id', 1);
        });
    }
}

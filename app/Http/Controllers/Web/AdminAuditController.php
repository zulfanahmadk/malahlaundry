<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Logging\FeatureLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function index(Request $request): View
    {
        $features = FeatureLog::features();
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'feature' => ['nullable', Rule::in($features)],
            'outcome' => ['nullable', Rule::in(['success', 'rejected', 'error'])],
            'role' => ['nullable', Rule::in(['admin', 'owner', 'cashier', 'anonymous'])],
            'actor_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = DB::table('audit_logs');
        if ($filters['from'] ?? null) {
            $query->where('occurred_at', '>=', CarbonImmutable::parse($filters['from'], 'Asia/Jakarta')->startOfDay()->utc());
        }
        if ($filters['to'] ?? null) {
            $query->where('occurred_at', '<', CarbonImmutable::parse($filters['to'], 'Asia/Jakarta')->addDay()->startOfDay()->utc());
        }
        foreach (['feature', 'outcome', 'actor_id'] as $field) {
            if ($filters[$field] ?? null) {
                $query->where($field, $filters[$field]);
            }
        }
        if ($filters['role'] ?? null) {
            $filters['role'] === 'anonymous'
                ? $query->whereNull('actor_role') : $query->where('actor_role', $filters['role']);
        }

        return view('admin.audit', [
            'logs' => $query->orderByDesc('id')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'features' => $features,
        ]);
    }
}

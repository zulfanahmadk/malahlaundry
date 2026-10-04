<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // Only aggregate counts leave this controller; no store records are loaded.
        $users = User::whereIn('role', ['owner', 'cashier'])->selectRaw(
            "COUNT(*) AS total, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) AS owners,
            SUM(CASE WHEN role = 'cashier' THEN 1 ELSE 0 END) AS cashiers"
        )->first();
        $branches = Branch::selectRaw('COUNT(*) AS total, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active')->first();
        $services = Service::withoutGlobalScope('branch')->selectRaw('COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active')->first();

        return view('admin.dashboard', ['pendingTickets' => \App\Models\SupportTicket::where('status', 'SUBMITTED')->count(), 'summary' => [
            'users' => (int) $users->total,
            'active_users' => (int) $users->active,
            'owners' => (int) $users->owners,
            'cashiers' => (int) $users->cashiers,
            'branches' => (int) $branches->total,
            'active_branches' => (int) $branches->active,
            'services' => (int) $services->total,
            'active_services' => (int) $services->active,
            'apk_releases' => DB::table('apk_releases')->count(),
        ]]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Exports\TransactionXlsxExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionFilterRequest;
use App\Models\Attendance;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use App\Services\UpdateUser;

class DashboardController extends Controller
{
    public function selectBranch(Request $request): RedirectResponse
    {
        $data = $request->validate(['branch_id' => 'required|integer|exists:branches,id']);
        $request->session()->put('branch_id', (int) $data['branch_id']);
        return redirect()->route('dashboard')->with('success', 'Cabang aktif berhasil diganti.');
    }

    /**
     * Dashboard Ringkasan & Statistik
     */
    public function index(): View
    {
        $today = today();

        $stats = [
            'today_omzet' => Transaction::whereDate('created_at', $today)->where('payment_status', 'LUNAS')->sum('total'),
            'month_omzet' => Transaction::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('payment_status', 'LUNAS')->sum('total'),
            'today_transactions_count' => Transaction::whereDate('created_at', $today)->count(),
            'active_laundry_count' => Transaction::whereIn('laundry_status', ['DITERIMA', 'SIAP_DIAMBIL'])->count(),
            'ready_pickup_count' => Transaction::where('laundry_status', 'SIAP_DIAMBIL')->count(),
            'unpaid_count' => Transaction::where('payment_status', 'BELUM')->count(),
        ];
        $stats['average'] = $stats['today_transactions_count'] ? Transaction::whereDate('created_at', $today)->avg('total') : 0;
        $weekly = collect(range(6, 0))->map(function ($offset) {
            $day = today()->subDays($offset);
            return ['label' => $day->locale('id')->translatedFormat('D'), 'value' => Transaction::whereDate('created_at', $day)->where('payment_status', 'LUNAS')->sum('total')];
        });
        $overdue = Transaction::where('laundry_status', 'SIAP_DIAMBIL')->whereNotNull('ready_at')->where('ready_at', '<=', now()->subDays(3))->count();

        $recentTransactions = Transaction::with(['customer', 'user', 'items'])
            ->latest()
            ->take(8)
            ->get();

        $todayAttendances = Attendance::with('user')
            ->whereDate('check_in_time', $today)
            ->latest('check_in_time')
            ->get();

        return view('dashboard.index', compact('stats', 'recentTransactions', 'todayAttendances', 'weekly', 'overdue'));
    }

    /**
     * Manajemen Master Layanan
     */
    public function services(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'unit' => 'nullable|in:kg,pcs,m2', 'speed' => 'nullable|in:REGULER,EXPRESS']);
        $services = Service::when($filters['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->when($filters['unit'] ?? null, fn ($q, $unit) => $q->where('unit', $unit))
            ->when($filters['speed'] ?? null, fn ($q, $speed) => $q->where('speed', $speed))->latest()->get();
        $stats = ['total' => Service::count(), 'kg' => Service::where('unit', 'kg')->count(), 'pcs' => Service::where('unit', 'pcs')->count(), 'devices' => \App\Models\DeviceSyncState::where('last_synced_at', '>=', Service::max('updated_at') ?? now())->count()];
        return view('dashboard.services', compact('services', 'stats'));
    }

    private function normalizeServiceDuration(Request $request): void
    {
        if ($request->filled('duration_value')) {
            $data = $request->validate(['duration_value' => 'required|integer|between:1,8760', 'duration_unit' => 'required|in:HOUR,DAY']);
            $request->merge(['duration_hours' => (int) $data['duration_value'] * ($data['duration_unit'] === 'DAY' ? 24 : 1)]);
        }
    }

    public function storeService(Request $request): RedirectResponse
    {
        $this->normalizeServiceDuration($request);
        $validated = $request->validate([
            'access_role_id' => 'nullable|integer|exists:access_roles,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs,m2',
            'price' => 'required|integer|min:0|max:1000000000',
            'speed' => 'required|in:REGULER,EXPRESS', 'duration_hours' => 'required|integer|between:1,8760', 'duration_unit' => 'sometimes|in:HOUR,DAY',
        ]);

        Service::create([
            'uuid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'price' => (int) $validated['price'],
            'is_active' => true,
            'speed' => $validated['speed'], 'duration_hours' => $validated['duration_hours'], 'duration_unit' => $validated['duration_unit'] ?? 'HOUR',
        ]);

        return back()->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function updateService(Request $request, string $uuid): RedirectResponse
    {
        $this->normalizeServiceDuration($request);
        $service = Service::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs,m2',
            'price' => 'required|integer|min:0|max:1000000000',
            'is_active' => 'required|boolean',
            'speed' => 'required|in:REGULER,EXPRESS', 'duration_hours' => 'required|integer|between:1,8760', 'duration_unit' => 'sometimes|in:HOUR,DAY',
        ]);

        $service->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'price' => (int) $validated['price'],
            'is_active' => $validated['is_active'],
            'speed' => $validated['speed'], 'duration_hours' => $validated['duration_hours'], 'duration_unit' => $validated['duration_unit'] ?? 'HOUR',
        ]);

        return back()->with('success', 'Layanan berhasil diperbarui.');
    }

    /**
     * Manajemen User (Kasir & Owner)
     */
    public function users(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'role' => 'nullable|in:owner,cashier', 'active' => 'nullable|in:0,1']);
        $query = User::where('branch_id', $request->attributes->get('branch_id'))->where('role', '!=', 'admin');
        $stats = ['total' => (clone $query)->count(), 'active' => (clone $query)->where('active', true)->count(), 'devices' => \App\Models\DeviceSyncState::count(), 'inactive' => (clone $query)->where('active', false)->count()];
        $users = $query->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('username', 'like', '%'.$term.'%')))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when(isset($filters['active']), fn ($q) => $q->where('active', $filters['active']))->latest()->get();
        return view('dashboard.users', compact('users', 'stats'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'role' => 'required|in:owner,cashier',
            'password' => 'required|string|min:8|max:72',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        \App\Support\Access::validateAssignment($request, $validated);
        User::create([
            'access_role_id' => $validated['access_role_id'],
            'menu_permissions' => $validated['menu_permissions'],
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'active' => true,
            'branch_id' => $validated['branch_id'],
        ]);

        return back()->with('success', 'Pengguna baru berhasil didaftarkan.');
    }

    public function toggleUser(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_if($user->isAdmin(), 403);
        abort_if(auth()->user()->accessRole?->branch_id && (int) $user->branch_id !== (int) auth()->user()->accessRole->branch_id, 403);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->active = !$user->active;
        $user->save();

        if (!$user->active) {
            $user->tokens()->delete();
        }

        return back()->with('success', 'Status akun pengguna diperbarui.');
    }

    public function editUser(User $user): View
    {
        abort_if($user->isAdmin(), 403);
        return view('dashboard.user-edit', compact('user'));
    }

    public function updateUser(Request $request, User $user, UpdateUser $update): RedirectResponse
    {
        $update->handle($request, $user);
        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Daftar Riwayat Transaksi
     */
    public function transactions(TransactionFilterRequest $request): View
    {
        $transactions = Transaction::with(['customer', 'user', 'items.service'])
            ->filter($request->validated())->latest()->orderBy('uuid')->paginate(15)->withQueryString();

        $stats = ['total' => Transaction::count(), 'received' => Transaction::where('laundry_status', 'DITERIMA')->count(), 'ready' => Transaction::where('laundry_status', 'SIAP_DIAMBIL')->count(), 'unpaid' => Transaction::where('payment_status', 'BELUM')->count()];
        return view('dashboard.transactions', compact('transactions', 'stats'));
    }

    /**
     * Riwayat Absensi Kasir (Check-In / Out dengan Foto Selfie)
     */
    public function attendances(Request $request): View
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|in:active,finished',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $attendances = Attendance::with('user')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$q.'%')->orWhere('username', 'like', '%'.$q.'%')))
            ->when($filters['user_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->whereNull('check_out_time'))
            ->when(($filters['status'] ?? '') === 'finished', fn ($query) => $query->whereNotNull('check_out_time'))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('check_in_time', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('check_in_time', '<=', $date))
            ->latest('check_in_time')
            ->orderByDesc('id')->paginate(15)->withQueryString();

        $users = User::where('branch_id', $request->attributes->get('branch_id'))->orderBy('name')->get(['id', 'name']);
        $today = Attendance::whereDate('check_in_time', today())->get();
        $hours = app(\App\Services\BranchHours::class);
        $branch = $request->attributes->get('branch');
        $onTime = $today->filter(fn ($row) => $hours->attendance($branch, $row->check_in_time) === 'TEPAT WAKTU')->pluck('user_id')->unique()->count();
        $late = $today->filter(fn ($row) => $hours->attendance($branch, $row->check_in_time) === 'TERLAMBAT')->pluck('user_id')->unique()->count();
        $present = $today->pluck('user_id')->unique()->count();
        $staff = User::where('branch_id', $branch->id)->where('role', 'cashier')->where('active', true)->count();
        $stats = ['present' => $present, 'on_time' => $onTime, 'late' => $late, 'missing' => max(0, $staff - $present), 'staff' => $staff];
        return view('dashboard.attendances', compact('attendances', 'users', 'stats', 'hours', 'branch'));
    }

    public function attendancePhoto(string $uuid, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['check_in', 'check_out'], true), 404);
        $attendance = Attendance::where('uuid', $uuid)->firstOrFail();
        $path = $attendance->{$type.'_photo_path'};
        abort_unless($path && Storage::disk('public')->exists($path), 404, 'Foto belum berhasil diunggah.');
        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * Web-Exclusive DLP Export (Download Laporan Rekapitulasi)
     * Hanya dapat diakses melalui browser admin yang terotentikasi sesi web.
     */
    public function exportReports(TransactionFilterRequest $request, TransactionXlsxExport $export): BinaryFileResponse
    {
        return $export->download($request->validated());
    }
}

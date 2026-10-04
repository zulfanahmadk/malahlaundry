<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BranchController;
use App\Http\Requests\TransactionFilterRequest;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeviceSyncState;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OwnerNotifications;
use App\Services\StoreConfiguration;
use App\Support\Workspace;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceController extends Controller
{
    private function customerQuery(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255', 'segment' => 'nullable|in:active,new,archived',
            'sort' => 'nullable|in:newest,spend,visits',
        ]);
        $query = Customer::withCount('transactions')->withSum('transactions', 'total')->withMax('transactions', 'created_at')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('phone', 'like', '%'.$term.'%')));
        if (($filters['segment'] ?? '') === 'archived') {
            $query->whereNotNull('archived_at');
        } elseif (($filters['segment'] ?? '') === 'active') {
            $query->whereNull('archived_at')->whereHas('transactions');
        } elseif (($filters['segment'] ?? '') === 'new') {
            $query->whereNull('archived_at')->whereDoesntHave('transactions');
        }
        $column = match ($filters['sort'] ?? 'newest') {
            'spend' => 'transactions_sum_total', 'visits' => 'transactions_count', default => 'created_at',
        };
        return $query->orderByDesc($column)->orderBy('uuid');
    }

    public function customers(Request $request): View
    {
        $customers = $this->customerQuery($request)->paginate(15)->withQueryString();
        $count = Customer::count();
        $stats = [
            'count' => $count, 'active' => Customer::whereNull('archived_at')->whereHas('transactions')->count(),
            'visits' => $count ? Transaction::count() / $count : 0,
            'spend' => $count ? Transaction::sum('total') / $count : 0,
            'repeat' => Customer::has('transactions', '>=', 2)->count(),
        ];
        return view('dashboard.customers', compact('customers', 'stats'));
    }

    public function customer(string $uuid): View
    {
        $customer = Customer::where('uuid', $uuid)->firstOrFail();
        $transactions = $customer->transactions()->with('items.service')->latest()->paginate(15)->withQueryString();
        $history = $customer->transactions()->orderBy('created_at')->get(['created_at', 'total', 'uuid']);
        $gaps = $history->pluck('created_at')->sliding(2)->map(fn ($pair) => $pair->first()->diffInDays($pair->last()));
        $stats = ['count' => $history->count(), 'total' => $history->sum('total'), 'gap' => $gaps->avg()];
        $items = DB::table('transaction_items')->whereIn('transaction_uuid', $customer->transactions()->select('uuid'))->get(['qty', 'unit']);
        $stats['kg'] = $history->count() ? $items->where('unit', 'kg')->sum('qty') / $history->count() : 0;
        $favorite = DB::table('transaction_items')->whereIn('transaction_uuid', $customer->transactions()->select('uuid'))
            ->select('service_name')->selectRaw('COUNT(*) as uses')->groupBy('service_name')->orderByDesc('uses')->first();
        return view('dashboard.customer-detail', compact('customer', 'transactions', 'stats', 'favorite'));
    }

    public function transaction(string $uuid): View
    {
        $transaction = Transaction::with(['customer', 'user', 'items.service'])->where('uuid', $uuid)->firstOrFail();
        return view('dashboard.transaction-detail', compact('transaction'));
    }

    public function reports(TransactionFilterRequest $request): View
    {
        $filters = $request->validated();
        $from = Carbon::parse($filters['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay();
        abort_if($to->lt($from), 422, 'Tanggal akhir harus setelah tanggal awal.');
        $filters['from'] = $from->toDateString();
        $filters['to'] = $to->toDateString();
        $query = Transaction::filter($filters);
        $count = (clone $query)->count();
        $paid = (clone $query)->where('payment_status', 'LUNAS');
        $income = (clone $paid)->sum('total');
        $stats = ['income' => $income, 'count' => $count, 'average' => $count ? (clone $query)->sum('total') / $count : 0, 'paid' => $count ? (clone $paid)->count() / $count * 100 : 0];
        $months = collect(range(5, 0))->map(function ($offset) {
            $start = now()->startOfMonth()->subMonths($offset);
            return ['label' => $start->locale('id')->translatedFormat('M Y'), 'value' => Transaction::where('payment_status', 'LUNAS')->whereBetween('created_at', [$start, $start->copy()->endOfMonth()])->sum('total')];
        });
        $services = DB::table('transaction_items')->whereIn('transaction_uuid', (clone $query)->select('uuid'))
            ->select('service_name')->selectRaw('COUNT(DISTINCT transaction_uuid) as orders, SUM(ROUND(qty * price)) as revenue')
            ->groupBy('service_name')->orderByDesc('revenue')->get();
        $total = $services->sum('revenue');
        $methods = (clone $paid)->select('payment_method')->selectRaw('COUNT(*) as count')->groupBy('payment_method')->get();
        $statuses = (clone $query)->select('laundry_status')->selectRaw('COUNT(*) as count')->groupBy('laundry_status')->get();
        return view('dashboard.reports', compact('stats', 'months', 'services', 'total', 'methods', 'statuses', 'filters'));
    }

    public function branches(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'active' => 'nullable|in:0,1', 'sort' => 'nullable|in:name,activity']);
        $query = Branch::query()->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('address', 'like', '%'.$term.'%')))
            ->when(isset($filters['active']), fn ($q) => $q->where('active', $filters['active']));
        $rows = $query->get()->map(function ($branch) {
            $branch->active_orders = Transaction::withoutGlobalScope('branch')->where('branch_id', $branch->id)->where('laundry_status', '!=', 'SELESAI')->count();
            $branch->staff_count = User::where('branch_id', $branch->id)->count();
            $branch->last_sync = DeviceSyncState::withoutGlobalScope('branch')->where('branch_id', $branch->id)->max('last_synced_at');
            return $branch;
        });
        $rows = ($filters['sort'] ?? '') === 'activity' ? $rows->sortByDesc('active_orders') : $rows->sortBy('name');
        $stats = ['total' => Branch::count(), 'active' => Branch::where('active', true)->count(), 'staff' => User::count(), 'inactive' => Branch::where('active', false)->count()];
        return view('dashboard.branches', compact('rows', 'stats'));
    }

    public function branchForm(?Branch $branch = null): View
    {
        return view('dashboard.branch-edit', ['branch' => $branch ?? new Branch(['active' => true, 'show_branch' => true, 'store_name' => 'Malah Laundry', 'radius_meters' => 200])]);
    }

    public function saveBranch(Request $request, BranchController $api, ?Branch $branch = null): RedirectResponse
    {
        $api->save($request, $branch);
        return redirect()->route('branches.index')->with('success', 'Cabang berhasil disimpan.');
    }

    public function hours(): View
    {
        return view('dashboard.hours', ['branch' => request()->attributes->get('branch')]);
    }

    public function saveHours(Request $request): RedirectResponse
    {
        // A blank date removes an exception; ignore the empty add-row before
        // applying the maximum so a full list of 20 entries remains editable.
        $exceptions = $request->input('opening_exceptions', []);
        if (is_array($exceptions)) {
            $request->merge(['opening_exceptions' => array_filter($exceptions, fn ($row) => !is_array($row) || !empty($row['date']))]);
        }
        $data = $request->validate([
            'opening_hours' => 'required|array|size:7', 'opening_hours.*' => 'array:day,open,from,to',
            'opening_hours.*.day' => 'required|integer|between:1,7|distinct', 'opening_hours.*.open' => 'required|boolean',
            'opening_hours.*.from' => 'required|date_format:H:i', 'opening_hours.*.to' => 'required|date_format:H:i',
            'opening_exceptions' => 'sometimes|array|max:20', 'opening_exceptions.*' => 'array:date,open,from,to,note',
            'opening_exceptions.*.date' => 'required|date_format:Y-m-d|distinct', 'opening_exceptions.*.open' => 'required|boolean',
            'opening_exceptions.*.from' => 'required|date_format:H:i', 'opening_exceptions.*.to' => 'required|date_format:H:i',
            'opening_exceptions.*.note' => 'nullable|string|max:100',
            'operational_preferences' => 'required|array:show_open_status,use_exceptions', 'operational_preferences.*' => 'required|boolean',
        ]);
        $data['opening_exceptions'] = array_values(array_filter($data['opening_exceptions'] ?? [], fn ($row) => ! empty($row['date'])));
        $data['operational_preferences'] = array_map(fn ($value) => (bool) $value, $data['operational_preferences']);
        foreach (['opening_hours', 'opening_exceptions'] as $key) {
            $data[$key] = array_map(fn ($row) => array_replace($row, ['open' => (bool) $row['open'], ...($key === 'opening_hours' ? ['day' => (int) $row['day']] : [])]), $data[$key]);
            foreach ($data[$key] as $row) {
                if ($row['open'] && $row['from'] >= $row['to']) {
                    throw ValidationException::withMessages([$key => 'Jam tutup harus setelah jam buka.']);
                }
            }
        }
        $request->attributes->get('branch')->update($data);
        return back()->with('success', 'Jam buka cabang berhasil disimpan.');
    }

    public function templates(StoreConfiguration $configuration): View
    {
        return view('dashboard.templates', ['store' => $configuration->read()]);
    }

    public function saveTemplates(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'templates' => 'required|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_SELESAI,WA_REMINDER', 'templates.*' => 'required|string|max:4000',
            'message_preferences' => 'required|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_REMINDER', 'message_preferences.*' => 'required|boolean',
        ]);
        DB::transaction(function () use ($request, $data): void {
            $branch = Branch::whereKey($request->attributes->get('branch_id'))->lockForUpdate()->firstOrFail();
            $branch->templates = array_replace($branch->templates ?? [], $data['templates']);
            $branch->message_preferences = array_replace($branch->message_preferences ?? [], array_map(fn ($value) => (bool) $value, $data['message_preferences']));
            $branch->save();
        });
        return back()->with('success', 'Template pesan cabang berhasil disimpan. Pengiriman tetap manual.');
    }

    public function profile(Request $request): View
    {
        $user = $request->user();
        $tokens = $user->tokens()->latest()->get();
        return view('dashboard.profile', compact('user', 'tokens'));
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255', 'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:40', 'current_password' => 'required|current_password:web',
            'password' => 'nullable|string|min:8|max:72|confirmed', 'revoke_tokens' => 'sometimes|boolean',
            'notify_login' => 'required|boolean', 'revoke_web' => 'sometimes|boolean',
        ]);
        $sensitive = $user->username !== $data['username'] || ! empty($data['password']);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->fill(collect($data)->except(['current_password', 'revoke_tokens', 'revoke_web'])->all())->save();
        if ($sensitive || $request->boolean('revoke_tokens')) {
            $user->tokens()->delete();
        }
        if ($sensitive || $request->boolean('revoke_web')) {
            $user->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->save();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
            }
            $request->session()->regenerate();
        }
        return back()->with('success', 'Profil berhasil diperbarui.'.($sensitive ? ' Perangkat Android perlu login kembali; data lokal tetap tersimpan.' : ''));
    }

    public function sync(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'status' => 'nullable|in:attention,current']);
        $devices = DeviceSyncState::with('user')->when($filters['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))->latest('last_seen_at')->get();
        if (($filters['status'] ?? '') === 'attention') {
            $devices = $devices->filter(fn ($d) => $d->attention || $d->pending_count);
        }
        if (($filters['status'] ?? '') === 'current') {
            $devices = $devices->filter(fn ($d) => ! $d->attention && ! $d->pending_count);
        }
        $all = DeviceSyncState::get();
        $stats = ['devices' => $all->count(), 'last' => $all->max('last_synced_at'), 'pending' => $all->sum('pending_count'), 'attention' => $all->filter(fn ($d) => $d->attention || $d->pending_count)->count()];
        return view('dashboard.sync', compact('devices', 'stats'));
    }

    public function notifications(Request $request, OwnerNotifications $feed): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:255', 'category' => 'nullable|in:CUCIAN,PRESENSI,SINKRON,AKUN', 'read' => 'nullable|in:0,1']);
        $all = $feed->all();
        $notifications = $all->filter(fn ($row) =>
            (empty($filters['q']) || mb_stripos($row['title'], $filters['q']) !== false)
            && (empty($filters['category']) || $filters['category'] === $row['category'])
            && (! isset($filters['read']) || (bool) $filters['read'] === $row['read']));
        return view('dashboard.notifications', compact('all', 'notifications'));
    }

    public function readNotifications(OwnerNotifications $feed): RedirectResponse
    {
        $feed->markAllRead();
        return back()->with('success', 'Semua pemberitahuan saat ini ditandai dibaca.');
    }

    private function csv(string $name, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headings, ';');
            foreach ($rows as $row) {
                // Prevent spreadsheet formula execution in customer-entered data.
                $row = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                fputcsv($stream, $row, ';');
            }
            fclose($stream);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function exportCustomers(Request $request): StreamedResponse
    {
        $rows = $this->customerQuery($request)->lazy(500)->map(fn ($c) => [$c->name, $c->phone, $c->transactions_count, $c->transactions_sum_total ?? 0, Workspace::date($c->transactions_max_created_at), $c->archived_at ? 'ARSIP' : 'AKTIF']);
        return $this->csv('pelanggan-'.now()->format('Ymd').'.csv', ['Nama', 'WhatsApp', 'Transaksi', 'Total belanja', 'Terakhir (WIB)', 'Status'], $rows);
    }

    public function exportAttendances(Request $request): StreamedResponse
    {
        $filters = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'q' => 'nullable|string|max:255', 'user_id' => 'nullable|integer|exists:users,id', 'status' => 'nullable|in:active,finished']);
        $query = Attendance::with('user')->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('check_in_time', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('check_in_time', '<=', $d))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('user', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('username', 'like', '%'.$term.'%'))))
            ->when(($filters['status'] ?? '') === 'active', fn ($q) => $q->whereNull('check_out_time'))
            ->when(($filters['status'] ?? '') === 'finished', fn ($q) => $q->whereNotNull('check_out_time'))
            ->latest('check_in_time')->orderByDesc('id');
        $rows = $query->lazy(500)->map(fn ($a) => [$a->user?->name, Workspace::date($a->check_in_time), Workspace::date($a->check_out_time), $a->check_out_time ? round($a->check_in_time->diffInMinutes($a->check_out_time)) : '', $a->check_out_time ? 'Selesai' : 'Berjalan']);
        return $this->csv('presensi-'.now()->format('Ymd').'.csv', ['Nama', 'Masuk (WIB)', 'Pulang (WIB)', 'Durasi menit', 'Status'], $rows);
    }
}

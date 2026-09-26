<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
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
            'active_laundry_count' => Transaction::whereIn('laundry_status', ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'])->count(),
            'ready_pickup_count' => Transaction::where('laundry_status', 'SIAP_DIAMBIL')->count(),
            'unpaid_count' => Transaction::where('payment_status', 'BELUM')->count(),
        ];

        $recentTransactions = Transaction::with(['customer', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $todayAttendances = Attendance::with('user')
            ->whereDate('check_in_time', $today)
            ->latest('check_in_time')
            ->get();

        return view('dashboard.index', compact('stats', 'recentTransactions', 'todayAttendances'));
    }

    /**
     * Manajemen Master Layanan
     */
    public function services(): View
    {
        $services = Service::latest()->get();
        return view('dashboard.services', compact('services'));
    }

    public function storeService(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs',
            'price' => 'required|numeric|min:0',
        ]);

        Service::create([
            'uuid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'price' => (int) $validated['price'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function updateService(Request $request, string $uuid): RedirectResponse
    {
        $service = Service::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs',
            'price' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $service->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'price' => (int) $validated['price'],
            'is_active' => $validated['is_active'],
        ]);

        return back()->with('success', 'Layanan berhasil diperbarui.');
    }

    /**
     * Manajemen User (Kasir & Owner)
     */
    public function users(): View
    {
        $users = User::latest()->get();
        return view('dashboard.users', compact('users'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'role' => 'required|in:owner,cashier',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'active' => true,
        ]);

        return back()->with('success', 'Pengguna baru berhasil didaftarkan.');
    }

    public function toggleUser(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->active = !$user->active;
        $user->save();

        return back()->with('success', 'Status akun pengguna diperbarui.');
    }

    /**
     * Daftar Riwayat Transaksi
     */
    public function transactions(Request $request): View
    {
        $query = Transaction::with(['customer', 'user', 'items.service']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('transaction_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', function ($c) use ($q) {
                        $c->where('name', 'like', "%{$q}%")
                          ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('laundry_status', $request->status);
        }

        if ($request->filled('payment')) {
            $query->where('payment_status', $request->payment);
        }

        $transactions = $query->latest()->paginate(15);

        return view('dashboard.transactions', compact('transactions'));
    }

    /**
     * Riwayat Absensi Kasir (Check-In / Out dengan Foto Selfie)
     */
    public function attendances(): View
    {
        $attendances = Attendance::with('user')
            ->latest('check_in_time')
            ->paginate(15);

        return view('dashboard.attendances', compact('attendances'));
    }

    /**
     * Web-Exclusive DLP Export (Download Laporan Rekapitulasi)
     * Hanya dapat diakses melalui browser admin yang terotentikasi sesi web.
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $fileName = 'laporan-malahlaundry-' . now()->format('Ymd-His') . '.csv';

        $transactions = Transaction::with(['customer', 'user'])
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM untuk Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No Transaksi',
                'Tanggal',
                'Nama Pelanggan',
                'No WhatsApp',
                'Kasir',
                'Status Laundry',
                'Status Pembayaran',
                'Subtotal (Rp)',
                'Total (Rp)',
                'URL Nota Digital',
            ]);

            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    $trx->transaction_number,
                    $trx->created_at->format('Y-m-d H:i:s'),
                    $trx->customer->name ?? '-',
                    $trx->customer->phone ?? '-',
                    $trx->user->name ?? '-',
                    $trx->laundry_status,
                    $trx->payment_status,
                    $trx->subtotal,
                    $trx->total,
                    $trx->public_receipt_url,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}

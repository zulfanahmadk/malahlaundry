<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\AdminNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(Request $request, AdminNotifications $feed): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:180', 'read' => ['nullable', Rule::in(['0', '1'])]]);
        $query = $feed->query($request->user())->with(['submittedBy:id,name,username', 'branch:id,name']);
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('support_tickets.number', 'like', '%'.$filters['q'].'%')->orWhere('support_tickets.subject', 'like', '%'.$filters['q'].'%'));
        }
        if ($request->filled('read')) {
            $filters['read'] === '1' ? $query->whereNotNull('ticket_read.read_at') : $query->whereNull('ticket_read.read_at');
        }
        return view('admin.notifications', [
            'tickets' => $query->orderByDesc('support_tickets.id')->paginate(20)->withQueryString(),
            'notificationSummary' => $feed->summary($request->user()),
        ]);
    }

    public function feed(Request $request, AdminNotifications $feed): JsonResponse
    {
        return response()->json([
            ...$feed->summary($request->user()),
            'pending_tickets' => SupportTicket::where('status', 'SUBMITTED')->count(),
            'notifications' => $feed->preview($request->user())->map(fn ($row) => [
                'title' => $row['title'], 'time' => $row['time_label'], 'url' => $row['url'], 'read' => $row['read'],
            ]),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function read(Request $request, AdminNotifications $feed): RedirectResponse
    {
        $data = $request->validate(['through_ticket_id' => 'required|integer|min:0']);
        $feed->markAllRead($request->user(), (int) $data['through_ticket_id']);
        return redirect()->route('admin.notifications.index')->with('success', 'Notifikasi tiket yang ditampilkan sudah ditandai dibaca.');
    }
}

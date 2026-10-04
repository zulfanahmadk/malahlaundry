<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Services\TicketWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function ownerIndex(Request $request): View
    {
        return $this->listing($request, false);
    }

    public function adminIndex(Request $request): View
    {
        return $this->listing($request, true);
    }

    private function listing(Request $request, bool $admin): View
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:180',
            'type' => ['nullable', Rule::in(array_keys(SupportTicket::TYPES))],
            'status' => ['nullable', Rule::in(array_keys(SupportTicket::STATUSES))],
        ]);
        $base = SupportTicket::query()->when(! $admin, fn ($query) => $query->where('submitted_by', $request->user()->id));
        $stats = [
            'total' => (clone $base)->count(), 'submitted' => (clone $base)->where('status', 'SUBMITTED')->count(),
            'working' => (clone $base)->whereIn('status', ['ACCEPTED', 'IN_PROGRESS'])->count(),
            'resolved' => (clone $base)->where('status', 'RESOLVED')->count(),
            'rejected' => (clone $base)->where('status', 'REJECTED')->count(),
        ];
        $query = (clone $base)->with(['submittedBy:id,name,username', 'branch:id,name,code']);
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('number', 'like', '%'.$filters['q'].'%')->orWhere('subject', 'like', '%'.$filters['q'].'%'));
        }
        foreach (['type', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }
        $tickets = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('tickets.index', compact('tickets', 'stats', 'admin'));
    }

    public function create(): View
    {
        return view('tickets.create', ['submissionUuid' => Str::uuid()]);
    }

    public function store(Request $request, TicketWorkflow $workflow): RedirectResponse
    {
        $ticket = $workflow->create($request);
        return redirect()->route('tickets.show', $ticket)->with('success', 'Tiket '.$ticket->number.' sudah terkirim ke admin.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $admin = $request->user()->isAdmin();
        abort_unless($admin || $ticket->submitted_by === $request->user()->id, 404);
        if ($admin) {
            app(\App\Services\AdminNotifications::class)->markRead($request->user(), $ticket);
        }
        $ticket->load(['submittedBy:id,name,username', 'branch:id,name,code', 'updates.attachments']);
        return view('tickets.show', compact('ticket', 'admin'));
    }

    public function update(Request $request, SupportTicket $ticket, TicketWorkflow $workflow): RedirectResponse
    {
        $workflow->update($request, $ticket);
        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Progres tiket berhasil diperbarui dan dapat dilihat owner.');
    }

    public function download(Request $request, SupportTicket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user()->isAdmin() || $ticket->submitted_by === $request->user()->id, 404);
        abort_unless($ticket->updates()->whereKey($attachment->ticket_update_id)->exists(), 404);
        $disk = Storage::disk('ticket_attachments');
        abort_unless($disk->exists($attachment->path), 404, 'Lampiran tidak tersedia.');
        return $disk->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, TicketWorkflow $workflow): RedirectResponse
    {
        $workflow->reply($request, $ticket);
        return redirect()->route($request->user()->isAdmin() ? 'admin.tickets.show' : 'tickets.show', $ticket)->with('success', 'Tanggapan sudah dikirim.');
    }
}

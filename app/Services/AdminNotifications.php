<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminNotifications
{
    public function query(User $admin): Builder
    {
        abort_unless($admin->active && $admin->isAdmin(), 403);
        return SupportTicket::query()->leftJoin('admin_ticket_reads as ticket_read', function ($join) use ($admin) {
            $join->on('ticket_read.support_ticket_id', '=', 'support_tickets.id')->where('ticket_read.user_id', $admin->id);
        })->select('support_tickets.*', 'ticket_read.read_at as notification_read_at');
    }

    public function summary(User $admin): array
    {
        $counts = $this->query($admin)->select([])->selectRaw('COUNT(*) as notification_total, COALESCE(SUM(CASE WHEN ticket_read.read_at IS NULL THEN 1 ELSE 0 END), 0) as notification_unread, COALESCE(MAX(support_tickets.id), 0) as latest_ticket_id')
            ->first();
        return ['total' => (int) $counts->notification_total, 'unread' => (int) $counts->notification_unread,
            'through_ticket_id' => (int) $counts->latest_ticket_id];
    }

    public function preview(User $admin): Collection
    {
        return $this->query($admin)->with(['submittedBy:id,name', 'branch:id,name'])
            ->orderByRaw('ticket_read.read_at IS NULL DESC')->orderByDesc('support_tickets.id')->limit(4)->get()
            ->map(fn ($ticket) => $this->notification($ticket));
    }

    public function notification(SupportTicket $ticket): array
    {
        return [
            'key' => 'ticket:'.$ticket->uuid, 'category' => 'TIKET',
            'title' => 'Tiket baru '.$ticket->number.' · '.$ticket->subject,
            'time' => $ticket->created_at, 'time_label' => Workspace::date($ticket->created_at).' WIB',
            'url' => route('admin.tickets.show', $ticket), 'read' => $ticket->notification_read_at !== null,
        ];
    }

    public function markRead(User $admin, SupportTicket $ticket): void
    {
        abort_unless($admin->active && $admin->isAdmin(), 403);
        DB::table('admin_ticket_reads')->insertOrIgnore([
            'user_id' => $admin->id, 'support_ticket_id' => $ticket->id, 'read_at' => now(),
        ]);
    }

    public function markAllRead(User $admin, int $throughTicketId): void
    {
        abort_unless($admin->active && $admin->isAdmin(), 403);
        // A snapshot cutoff keeps tickets arriving after the form was opened unread.
        DB::transaction(function () use ($admin, $throughTicketId) {
            SupportTicket::where('id', '<=', $throughTicketId)->whereNotIn('id', DB::table('admin_ticket_reads')->where('user_id', $admin->id)->select('support_ticket_id'))->select('id')->chunkById(500, function ($tickets) use ($admin) {
                $rows = $tickets->map(fn ($ticket) => [
                    'user_id' => $admin->id, 'support_ticket_id' => $ticket->id, 'read_at' => now(),
                ])->all();
                DB::table('admin_ticket_reads')->insertOrIgnore($rows);
            });
        });
    }
}

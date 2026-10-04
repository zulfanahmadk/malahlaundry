<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketWorkflow
{
    public function __construct(private TicketAttachments $attachments)
    {
    }

    public function create(Request $request): SupportTicket
    {
        abort_unless($request->user()?->isOwner(), 403);
        $data = $request->validate([
            'submission_uuid' => 'required|uuid',
            'type' => ['required', Rule::in(array_keys(SupportTicket::TYPES))],
            'platform' => ['required', Rule::in(array_keys(SupportTicket::PLATFORMS))],
            'subject' => 'required|string|max:180', 'description' => 'required|string|max:10000',
            ...$this->attachments->rules(),
        ], $this->attachments->messages());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return $this->attachments->transaction(function (array &$paths) use ($request, $data) {
                    $owner = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                    abort_unless($owner->active && $owner->isOwner(), 403);
                    $existing = SupportTicket::where('uuid', $data['submission_uuid'])->first();
                    if ($existing) {
                        abort_unless($existing->submitted_by === $owner->id, 404);
                        return $existing;
                    }
                    $ticket = SupportTicket::create([
                        'uuid' => $data['submission_uuid'],
                        'number' => random_int(10000, 99999).'/'.$data['type'].'/'.random_int(10000, 99999),
                        'submitted_by' => $owner->id, 'branch_id' => $request->attributes->get('branch_id'),
                        'type' => $data['type'], 'platform' => $data['platform'],
                        'subject' => $data['subject'], 'description' => $data['description'],
                        'status' => 'SUBMITTED', 'progress' => 0,
                    ]);
                    $this->history($ticket, $owner, 'Tiket dikirim ke admin.', $request, $paths);
                    return $ticket;
                });
            } catch (UniqueConstraintViolationException) {
                // Unique indexes reserve both identifiers; retry random-number collisions.
            }
        }
        throw ValidationException::withMessages(['subject' => 'Nomor tiket belum dapat dibuat. Silakan kirim kembali.']);
    }

    public function update(Request $request, SupportTicket $ticket): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'revision' => 'required|integer|min:1',
            'status' => ['required', Rule::in(array_keys(SupportTicket::STATUSES))],
            'progress' => 'nullable|integer|min:0|max:100',
            'message' => 'required|string|max:10000',
            ...$this->attachments->rules(),
        ], $this->attachments->messages());
        $this->attachments->transaction(function (array &$paths) use ($request, $ticket, $data) {
            $ticket = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if ($ticket->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Tiket sudah diperbarui. Muat ulang halaman sebelum menyimpan.']);
            }
            $allowed = SupportTicket::TRANSITIONS[$ticket->status];
            if ($ticket->isClosed() || ($data['status'] !== $ticket->status && ! in_array($data['status'], $allowed, true)) || $data['status'] === 'SUBMITTED') {
                throw ValidationException::withMessages(['status' => 'Perubahan status tiket tidak sesuai alur.']);
            }
            $progress = match ($data['status']) {
                'RESOLVED' => 100, 'REJECTED', 'ACCEPTED' => 0,
                default => (int) ($data['progress'] ?? 0),
            };
            if ($data['status'] === 'IN_PROGRESS' && ($progress < 1 || $progress > 99 || $progress < $ticket->progress)) {
                throw ValidationException::withMessages(['progress' => 'Progres pengerjaan harus 1–99% dan tidak boleh berkurang.']);
            }
            $ticket->fill([
                'status' => $data['status'], 'progress' => $progress,
                'result' => in_array($data['status'], ['RESOLVED', 'REJECTED'], true) ? $data['message'] : null,
                'revision' => $ticket->revision + 1,
            ])->save();
            $this->history($ticket, $request->user(), $data['message'], $request, $paths);
        });
    }

    public function reply(Request $request, SupportTicket $ticket): void
    {
        $message = $request->validate(['message' => 'required|string|max:10000', ...$this->attachments->rules()], $this->attachments->messages())['message'];
        $this->attachments->transaction(function (array &$paths) use ($request, $ticket, $message) {
            $ticket = SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $actor = $request->user();
            abort_unless($actor?->isAdmin() || ($actor?->isOwner() && $ticket->submitted_by === $actor->id), 404);
            $ticket->increment('revision');
            $this->history($ticket, $actor, $message, $request, $paths);
        });
    }

    private function history(SupportTicket $ticket, User $actor, string $message, Request $request, array &$paths): void
    {
        $update = $ticket->updates()->create([
            'actor_id' => $actor->id, 'actor_name' => $actor->name, 'actor_role' => $actor->role,
            'status' => $ticket->status, 'progress' => $ticket->progress, 'message' => $message,
        ]);
        $this->attachments->store($update, $request->file('attachments', []), $paths);
    }
}

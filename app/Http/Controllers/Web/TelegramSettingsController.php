<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramNotification;
use App\Models\TelegramDelivery;
use App\Models\TelegramSubscription;
use App\Services\TelegramBot;
use App\Services\TelegramNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TelegramSettingsController extends Controller
{
    public function edit(Request $request, TelegramBot $bot)
    {
        $subscription = $this->subscription($request);
        return view('dashboard.telegram', [
            'subscription' => $subscription,
            'botConfigured' => $bot->configured(),
            'deliveries' => TelegramDelivery::where('subscription_id', $subscription->id)->latest('id')->paginate(20),
        ]);
    }

    public function update(Request $request, TelegramBot $bot)
    {
        $data = $request->validate([
            'chat_id' => ['nullable', 'string', 'max:32', 'regex:/^-?[1-9][0-9]{0,19}$/'],
            'enabled' => ['required', 'boolean'],
            'notify_created' => ['required', 'boolean'],
            'notify_picked_up' => ['required', 'boolean'],
        ], ['chat_id.regex' => 'Chat ID harus berupa angka dari message.chat.id, bukan username bot.']);
        $data['chat_id'] ??= null;
        if ($request->boolean('enabled') && (! $bot->configured() || empty($data['chat_id']))) {
            throw ValidationException::withMessages(['chat_id' => 'Untuk mengaktifkan notifikasi, isi Chat ID dan konfigurasi token bot di server.']);
        }
        $subscription = $this->subscription($request, true);
        DB::transaction(function () use ($subscription, $data) {
            $subscription = TelegramSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            $changedChat = $subscription->chat_id !== $data['chat_id'];
            $subscription->update($data);
            $pending = TelegramDelivery::where('subscription_id', $subscription->id)->where('status', 'pending');
            if ($changedChat || ! $subscription->enabled) {
                $pending->update(['status' => 'cancelled', 'last_error' => 'Penerima diganti atau notifikasi dinonaktifkan.']);
            } else {
                $disabled = array_filter(['created', 'picked_up'], fn ($event) => ! $subscription->{'notify_'.$event});
                $pending->whereIn('event', $disabled)->update(['status' => 'cancelled', 'last_error' => 'Jenis notifikasi dinonaktifkan.']);
            }
        });
        return to_route('settings.telegram.edit')->with('success', 'Pengaturan Telegram untuk akun Anda di cabang ini berhasil disimpan.');
    }

    public function test(Request $request, TelegramBot $bot, TelegramNotifications $notifications)
    {
        $subscription = $this->subscription($request);
        if (! $bot->configured() || ! $subscription->chat_id) {
            throw ValidationException::withMessages(['telegram' => 'Simpan Chat ID dan konfigurasi token bot di server terlebih dahulu.']);
        }
        abort_unless($notifications->allowed($subscription), 403, 'Akses melihat transaksi diperlukan untuk notifikasi Telegram.');
        $branch = $request->attributes->get('branch');
        $delivery = TelegramDelivery::create([
            'subscription_id' => $subscription->id, 'branch_id' => $branch->id,
            'event' => 'test', 'chat_id' => $subscription->chat_id, 'available_at' => now(),
            'text' => "🔔 TES NOTIFIKASI MALAH LAUNDRY\nCabang: ".$branch->name."\nPenerima: ".$request->user()->name."\nWaktu: ".now()->timezone('Asia/Jakarta')->format('d/m/Y H:i')." WIB\nKoneksi bot Telegram berhasil.",
        ]);
        (new SendTelegramNotification($delivery->id))->handle($bot, $notifications);
        $delivery->refresh();
        if ($delivery->status !== 'sent') {
            return to_route('settings.telegram.edit')->withErrors(['telegram' => $delivery->last_error]);
        }
        return to_route('settings.telegram.edit')->with('success', 'Telegram mengonfirmasi pesan percobaan berhasil dikirim. Periksa chat penerima.');
    }

    public function retry(Request $request, int $delivery, TelegramNotifications $notifications)
    {
        $subscription = $this->subscription($request);
        DB::transaction(function () use ($delivery, $subscription, $notifications) {
            $record = TelegramDelivery::whereKey($delivery)->where('subscription_id', $subscription->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'failed', 422, 'Hanya pengiriman gagal yang dapat dicoba ulang.');
            abort_unless(
                $record->chat_id === $subscription->chat_id && $notifications->allowed($subscription)
                && ($record->event === 'test' || ($subscription->enabled && $subscription->{'notify_'.$record->event})),
                422,
                'Penerima, akses, atau preferensi sudah berubah. Gunakan pesan percobaan untuk penerima baru.'
            );
            $record->update(['status' => 'pending', 'attempts' => 0, 'available_at' => now(), 'queued_at' => null, 'queue_job_id' => null, 'last_error' => null]);
        });
        $notifications->dispatchSafely();
        return to_route('settings.telegram.edit')->with('success', 'Pesan dijadwalkan untuk dicoba ulang. Hasilnya akan tampil di riwayat pengiriman.');
    }

    private function subscription(Request $request, bool $persist = false): TelegramSubscription
    {
        $method = $persist ? 'firstOrCreate' : 'firstOrNew';
        return TelegramSubscription::$method([
            'user_id' => $request->user()->id, 'branch_id' => $request->attributes->get('branch_id'),
        ], ['enabled' => false, 'notify_created' => true, 'notify_picked_up' => true]);
    }
}

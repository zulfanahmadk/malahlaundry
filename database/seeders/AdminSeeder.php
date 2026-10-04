<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $existing = User::where('username', $username)->first();
        if ($existing && ! $existing->isAdmin()) {
            throw new \RuntimeException('ADMIN_USERNAME sudah dipakai akun non-admin. Pilih username admin lain.');
        }
        if ($existing) {
            $this->command?->info('Akun dengan username admin sudah ada; tidak diubah oleh seeder.');
            return;
        }
        $password = env('ADMIN_INITIAL_PASSWORD') ?: Str::password(24);
        $user = User::create([
            'name' => 'Administrator Sistem', 'username' => $username,
            'email' => null, 'password' => $password, 'role' => 'admin',
            'active' => true, 'branch_id' => 1,
        ]);
        if (! app()->environment('testing')) {
            if (! Storage::disk('local')->put('admin-initial-password.txt', "Username: {$user->username}\nPassword: {$password}\nGanti password setelah login pertama, lalu hapus file ini.\n")) {
                $user->delete();
                throw new \RuntimeException('Kredensial admin gagal disimpan. Periksa izin storage lalu jalankan seeder kembali.');
            }
            $this->command?->info('Akun admin dibuat. Kredensial awal: storage/app/private/admin-initial-password.txt');
        }
    }
}

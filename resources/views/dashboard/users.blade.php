@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page_title', 'Manajemen Pengguna & Kasir')

@section('content')
<div class="grid-layout">
    <!-- Form Tambah User -->
    <div class="card">
        <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem;">Daftarkan Kasir / Pengguna</h2>
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Siti Rahma" required>
            </div>
            <div class="form-group">
                <label for="username">Username (Untuk Login POS &amp; Web)</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: sitikasir" required>
            </div>
            <div class="form-group">
                <label for="role">Hak Akses (Role)</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="cashier">Kasir (Android POS &amp; Shift)</option>
                    <option value="owner">Owner (Hak Akses Penuh)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                Daftarkan Pengguna
            </button>
        </form>
    </div>

    <!-- Tabel Daftar User -->
    <div class="card">
        <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem;">Daftar Pengguna Sistem</h2>
        <div class="table-responsive">
            <table style="min-width: 560px;">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Peran (Role)</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td><code>{{ $user->username }}</code></td>
                            <td>
                                @if($user->role === 'owner')
                                    <span class="badge badge-primary">Owner</span>
                                @else
                                    <span class="badge badge-warning">Kasir</span>
                                @endif
                            </td>
                            <td>
                                @if($user->active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.toggle', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                            {{ $user->active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">(Sedang Login)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

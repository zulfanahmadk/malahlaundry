@extends('layouts.app')

@section('title', 'Manajemen Layanan')
@section('page_title', 'Master Data Layanan & Harga')

@section('content')
<style>
    .grid-layout {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 1.5rem;
    }
    @media (max-width: 900px) {
        .grid-layout {
            grid-template-columns: 1fr;
        }
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        color: var(--text-main);
    }
    .form-control {
        width: 100%;
        padding: 0.6rem 0.75rem;
        font-size: 0.85rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: #FFFFFF;
        outline: none;
    }
    .form-control:focus {
        border-color: var(--primary);
    }
</style>

<div class="grid-layout">
    <!-- Form Tambah Layanan -->
    <div class="card">
        <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem;">Tambah Layanan Baru</h2>
        <form action="{{ route('services.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Nama Layanan</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Cuci Komplit Reguler" required>
            </div>
            <div class="form-group">
                <label for="unit">Satuan</label>
                <select id="unit" name="unit" class="form-control" required>
                    <option value="kg">Kilogram (KG)</option>
                    <option value="pcs">Pieces / Satuan (Pcs)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="price">Harga Satuan (Rp)</label>
                <input type="number" id="price" name="price" class="form-control" placeholder="Contoh: 7000" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                Simpan Layanan
            </button>
        </form>
    </div>

    <!-- Tabel Daftar Layanan -->
    <div class="card">
        <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem;">Daftar Layanan Tersedia</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama Layanan</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $srv)
                        <tr>
                            <td><strong>{{ $srv->name }}</strong></td>
                            <td><span class="badge badge-primary">{{ strtoupper($srv->unit) }}</span></td>
                            <td><strong>Rp{{ number_format($srv->price, 0, ',', '.') }}</strong></td>
                            <td>
                                @if($srv->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('services.update', $srv->uuid) }}" method="POST" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $srv->name }}">
                                    <input type="hidden" name="unit" value="{{ $srv->unit }}">
                                    <input type="hidden" name="price" value="{{ $srv->price }}">
                                    <input type="hidden" name="is_active" value="{{ $srv->is_active ? '0' : '1' }}">
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                        {{ $srv->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada data layanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

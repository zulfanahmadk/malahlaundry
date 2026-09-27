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
                <input type="text" id="name" name="name" value="{{ old('service_uuid') ? '' : old('name') }}" class="form-control" placeholder="Contoh: Cuci Komplit Reguler" maxlength="255" required>
            </div>
            <div class="form-group">
                <label for="unit">Satuan</label>
                <select id="unit" name="unit" class="form-control" required>
                    <option value="kg" @selected(!old('service_uuid') && old('unit') === 'kg')>Kilogram (KG)</option>
                    <option value="pcs" @selected(!old('service_uuid') && old('unit') === 'pcs')>Pieces / Satuan (Pcs)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="price">Harga Satuan (Rp)</label>
                <input type="number" id="price" name="price" value="{{ old('service_uuid') ? '' : old('price') }}" class="form-control" placeholder="Contoh: 7000" min="0" max="1000000000" step="1" required>
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
                                @php($editing = old('service_uuid') === $srv->uuid)
                                <details @if($editing) open @endif>
                                    <summary class="btn btn-secondary">Edit Layanan</summary>
                                    <form action="{{ route('services.update', $srv->uuid) }}" method="POST" style="min-width: 200px; margin-top: 1rem;">
                                        @csrf
                                        <input type="hidden" name="service_uuid" value="{{ $srv->uuid }}">
                                        <div class="form-group">
                                            <label for="name-{{ $srv->uuid }}">Nama layanan</label>
                                            <input id="name-{{ $srv->uuid }}" name="name" value="{{ $editing ? old('name') : $srv->name }}" class="form-control" maxlength="255" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="unit-{{ $srv->uuid }}">Satuan</label>
                                            <select id="unit-{{ $srv->uuid }}" name="unit" class="form-control">
                                                <option value="kg" @selected(($editing ? old('unit') : $srv->unit) === 'kg')>Kilogram (KG)</option>
                                                <option value="pcs" @selected(($editing ? old('unit') : $srv->unit) === 'pcs')>Pieces / Satuan (Pcs)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="price-{{ $srv->uuid }}">Harga satuan (Rp)</label>
                                            <input type="number" id="price-{{ $srv->uuid }}" name="price" value="{{ $editing ? old('price') : $srv->price }}" min="0" max="1000000000" step="1" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="active-{{ $srv->uuid }}">Status</label>
                                            <select id="active-{{ $srv->uuid }}" name="is_active" class="form-control">
                                                <option value="1" @selected((int) ($editing ? old('is_active') : $srv->is_active) === 1)>Aktif</option>
                                                <option value="0" @selected((int) ($editing ? old('is_active') : $srv->is_active) === 0)>Nonaktif</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </form>
                                </details>
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

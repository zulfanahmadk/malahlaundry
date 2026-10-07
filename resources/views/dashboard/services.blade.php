@extends('layouts.app')
@section('figma_node', '127-1289')
@section('title', 'Layanan')
@section('page_title', 'Layanan')
@section('page_subtitle', 'Kelola harga dan estimasi layanan cabang aktif')
@section('page_actions')<button class="btn btn-primary" data-open-dialog="service-create">
    <x-figma-icon name="imgIconPlus" />Tambah layanan</button>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Total layanan" :value="$stats['total']" note="Pada cabang ini" icon="imgIconService" />
    <x-workspace-stat label="Kiloan" :value="$stats['kg']" note="Satuan kg" icon="imgIconService1" tone="green" />
    <x-workspace-stat label="Satuan" :value="$stats['pcs']" note="Satuan pcs" icon="imgIconService2" tone="teal" />
    <x-workspace-stat label="Perangkat tersinkron" :value="$stats['devices']" note="Sejak perubahan layanan terakhir" icon="imgIconRefresh" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input type="search" name="q" placeholder="Cari layanan" aria-label="Cari layanan" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="unit" aria-label="Jenis layanan">
                <option value="">Semua jenis</option>@foreach(['kg'=>'Kiloan (kg)','pcs'=>'Satuan (pcs)','m2'=>'Luas (m²)'] as $key=>$label)<option value="{{ $key }}" @selected(request('unit') === $key)>{{ $label }}</option>@endforeach</select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="speed" aria-label="Kecepatan layanan">
                <option value="">Semua kecepatan</option>
                <option value="REGULER" @selected(request('speed') === 'REGULER')>Reguler</option>
                <option value="EXPRESS" @selected(request('speed') === 'EXPRESS')>Express</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('services.index') }}">Reset</a>
    <span class="count">{{ $services->count() }} data</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th>Jenis</th>
                    <th>Kecepatan</th>
                    <th>Harga</th>
                    <th>Estimasi</th>
                    <th>Status / aksi</th>
                </tr>
            </thead>
            <tbody>@forelse($services as $service)<tr>
                    <td>{{ $service->name }}</td>
                    <td>{{ ['kg'=>'Kiloan','pcs'=>'Satuan','m2'=>'Luas'][$service->unit] ?? $service->unit }}</td>
                    <td>{{ ucfirst(strtolower($service->speed)) }}</td>
                    <td class="table-nowrap">{{ \App\Support\Workspace::money($service->price) }}/{{ $service->unit === 'm2' ? 'm²' : $service->unit }}</td>
                    <td class="table-nowrap">{{ $service->duration_unit === 'DAY' ? ($service->duration_hours / 24).' hari' : $service->duration_hours.' jam' }}</td>
                    <td>
                        <div class="table-actions">
                            <span class="badge {{ $service->is_active ? 'badge-success' : '' }}">{{ $service->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                            <button class="btn btn-small btn-secondary" data-open-dialog="service-{{ $service->uuid }}">Edit</button>
                        </div>
                    </td>
                </tr>@empty<tr>
                    <td colspan="6" class="empty">Belum ada layanan yang sesuai filter.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="insights">
    <div class="insight">
        <h3>Harga dan estimasi</h3>
        <p>Perubahan berlaku pada transaksi baru setelah perangkat menyinkronkan data. Harga pada transaksi lama tetap mengikuti catatan saat transaksi dibuat.</p>
    </div>
    <div class="notice orange">
        <x-figma-icon name="imgIconAlert" />Perangkat offline memakai data layanan terakhir yang tersimpan. Pastikan perubahan disinkronkan sebelum menerima transaksi baru.</div>
</div>
@foreach(collect([null])->concat($services) as $service)
@php
    $dialogId = $service ? 'service-'.$service->uuid : 'service-create';
    $isFailedForm = old('_workspace_dialog') === $dialogId;
    $formValue = fn ($key, $fallback = null) => $isFailedForm ? old($key, $fallback) : $fallback;
@endphp
<dialog id="{{ $service ? 'service-'.$service->uuid : 'service-create' }}" class="dialog">
    <div class="section-head">
        <h2>{{ $service ? 'Edit layanan' : 'Tambah layanan' }}</h2>
        <button class="btn btn-secondary btn-small" data-close-dialog type="button">Tutup</button>
    </div>
    <form method="POST" action="{{ $service ? route('services.update', $service->uuid) : route('services.store') }}">@csrf
        <input type="hidden" name="_workspace_dialog" value="{{ $dialogId }}">
        <div class="form-grid">
            <div class="form-group span-all">
                <label>Nama layanan<input name="name" value="{{ $formValue('name', $service?->name) }}" maxlength="255" required>
                </label>
            </div>
            <div class="form-group">
                <label>Satuan<span class="select-field">
                        <select name="unit">@foreach(['kg'=>'kg','pcs'=>'pcs','m2'=>'m²'] as $key=>$label)<option value="{{ $key }}" @selected($formValue('unit', $service?->unit ?? 'kg') === $key)>{{ $label }}</option>@endforeach</select>
                        <x-figma-icon name="imgIconChevron" />
                    </span>
                </label>
            </div>
            <div class="form-group">
                <label>Harga per satuan<input type="number" name="price" min="0" max="1000000000" value="{{ $formValue('price', $service?->price) }}" required>
                </label>
            </div>
            <div class="form-group">
                <label>Kecepatan<span class="select-field">
                        <select name="speed">
                            <option value="REGULER" @selected($formValue('speed', $service?->speed) !== 'EXPRESS')>Reguler</option>
                            <option value="EXPRESS" @selected($formValue('speed', $service?->speed) === 'EXPRESS')>Express</option>
                        </select>
                        <x-figma-icon name="imgIconChevron" />
                    </span>
                </label>
            </div>
            <div class="form-group">
                <label>Estimasi durasi<input type="number" name="duration_value" min="1" max="8760" value="{{ $formValue('duration_value', $service?->duration_unit === 'DAY' ? $service->duration_hours / 24 : ($service?->duration_hours ?? 48)) }}" required>
                </label>
            </div>
            <div class="form-group"><label>Satuan estimasi<select name="duration_unit"><option value="HOUR" @selected($formValue('duration_unit', $service?->duration_unit) !== 'DAY')>Jam</option><option value="DAY" @selected($formValue('duration_unit', $service?->duration_unit) === 'DAY')>Hari</option></select></label></div>
            @if($service)<div class="form-group">
                <label>Status<span class="select-field">
                        <select name="is_active">
                            <option value="1" @selected((int) $formValue('is_active', $service->is_active) === 1)>Aktif</option>
                            <option value="0" @selected((int) $formValue('is_active', $service->is_active) === 0)>Nonaktif</option>
                        </select>
                        <x-figma-icon name="imgIconChevron" />
                    </span>
                </label>
            </div>@endif</div>
        <button class="btn btn-primary">Simpan layanan</button>
    </form>
</dialog>
@endforeach
@endsection

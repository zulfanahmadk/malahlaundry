@extends('layouts.app')
@section('figma_node', '127-1524')
@section('title', 'Pengaturan cabang')
@section('page_title', $branch->exists ? 'Edit cabang' : 'Tambah cabang')
@section('content')
<div class="card" style="max-width:900px">
    <div class="section-head">
        <h2>Informasi cabang</h2>
        <a href="{{ route('branches.index') }}">Kembali ke daftar</a>
    </div>
    <form action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" method="POST">@csrf<div class="form-grid">
            @foreach(['store_name'=>'Nama toko','name'=>'Nama cabang','code'=>'Kode cabang','phone'=>'WhatsApp'] as $key=>$label)<div class="form-group">
                <label for="branch-{{ $key }}">{{ $label }}</label>
                <input id="branch-{{ $key }}" name="{{ $key }}" value="{{ old($key, $branch->$key) }}" maxlength="{{ $key === 'code' ? 30 : ($key === 'phone' ? 40 : 100) }}" @required($key !== 'phone')>
            </div>@endforeach
            <div class="form-group span-all">
                <label for="branch-address">Alamat</label>
                <textarea id="branch-address" name="address" maxlength="1000">{{ old('address', $branch->address) }}</textarea>
            </div>
            <div class="form-group">
                <label for="branch-active">Status</label>
                <span class="select-field">
                    <select id="branch-active" name="active">
                        <option value="1" @selected((int) old('active', $branch->active) === 1)>Aktif</option>
                        <option value="0" @selected((int) old('active', $branch->active) === 0)>Nonaktif</option>
                    </select>
                    <x-figma-icon name="imgIconChevron" />
                </span>
            </div>
            <div class="form-group">
                <label for="branch-show">Tampilkan nama cabang di nota</label>
                <span class="select-field">
                    <select id="branch-show" name="show_branch">
                        <option value="1" @selected((int) old('show_branch', $branch->show_branch) === 1)>Ya</option>
                        <option value="0" @selected((int) old('show_branch', $branch->show_branch) === 0)>Tidak</option>
                    </select>
                    <x-figma-icon name="imgIconChevron" />
                </span>
            </div>
            <div class="form-group">
                <label for="branch-lat">Latitude presensi</label>
                <input id="branch-lat" type="number" step="0.0000001" min="-90" max="90" name="latitude" value="{{ old('latitude', $branch->latitude) }}">
            </div>
            <div class="form-group">
                <label for="branch-lon">Longitude presensi</label>
                <input id="branch-lon" type="number" step="0.0000001" min="-180" max="180" name="longitude" value="{{ old('longitude', $branch->longitude) }}">
            </div>
            <div class="form-group">
                <label for="branch-radius">Radius presensi (meter)</label>
                <input id="branch-radius" type="number" min="10" max="10000" name="radius_meters" value="{{ old('radius_meters', $branch->radius_meters) }}" required>
            </div>
            <p class="form-help">Isi kedua koordinat untuk membatasi lokasi presensi. Kosongkan keduanya jika cabang belum memakai batas lokasi.</p>
        </div>
        <div class="actions" style="margin-top:20px">
            <button class="btn btn-primary">Simpan cabang</button>
            <a class="btn btn-secondary" href="{{ route('branches.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Pengaturan Toko')
@section('page_title', 'Pengaturan Toko & Nota')
@section('content')
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <h2 class="section-title" style="margin-bottom: 1rem;">Identitas toko</h2>
        <div class="form-group"><label for="name">Nama toko</label><input id="name" class="form-control" name="name" value="{{ old('name', $store['name']) }}" maxlength="100" required></div>
        <div class="form-group"><label for="phone">Nomor telepon / WhatsApp toko</label><input id="phone" class="form-control" name="phone" value="{{ old('phone', $store['phone']) }}" maxlength="40"></div>
        <div class="form-group"><label for="address">Alamat toko</label><textarea id="address" class="form-control" name="address" rows="3" maxlength="1000">{{ old('address', $store['address']) }}</textarea></div>
        @if($store['logo_url'])<img src="{{ $store['logo_url'] }}" alt="Logo toko saat ini" style="width: 96px; height: 96px; object-fit: contain; margin-bottom: 1rem;">@endif
        <div class="form-group"><label for="logo">Logo toko (JPG, PNG, WebP; maksimal 1 MB)</label><input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
        <label><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Hapus logo saat ini</label>
        <p style="color: var(--text-muted); margin-top: .75rem;">Identitas dan logo tampil pada aplikasi, website, dan nota digital. Android memperbarui pengaturan saat sinkronisasi.</p>
    </div>
    <div class="card">
        <h2 class="section-title">Syarat dan ketentuan nota digital</h2>
        <div class="form-group"><label for="receipt_terms">Isi ketentuan</label><textarea id="receipt_terms" name="receipt_terms" class="form-control" rows="6" maxlength="5000" placeholder="Tuliskan ketentuan laundry Anda">{{ old('receipt_terms', $store['receipt_terms']) }}</textarea></div>
    </div>
    <div class="card">
        <h2 class="section-title">Format pesan WhatsApp</h2>
        <p style="color: var(--text-muted); margin: 1rem 0; overflow-wrap: anywhere;">Variabel: {nama}, {no_transaksi}, {tanggal}, {items}, {total}, {sisa_bayar}, {status_bayar}, {url_nota}, {outlet}, {alamat_outlet}, {telepon_outlet}.</p>
        @foreach(['WA_DITERIMA' => 'Diterima', 'WA_SIAP_DIAMBIL' => 'Siap diambil', 'WA_SELESAI' => 'Selesai / sudah diambil'] as $type => $label)
            <div class="form-group"><label for="{{ $type }}">{{ $label }}</label><textarea id="{{ $type }}" class="form-control" name="templates[{{ $type }}]" rows="7" maxlength="4000" required>{{ old('templates.'.$type, $store['templates'][$type]) }}</textarea></div>
        @endforeach
        <button type="submit" class="btn btn-primary">Simpan pengaturan toko</button>
    </div>
</form>
@endsection

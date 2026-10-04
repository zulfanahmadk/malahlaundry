@extends('layouts.app')
@section('figma_node', '127-2347')
@section('title', 'Jam buka')
@section('page_title', 'Jam buka')
@section('page_subtitle', 'Jadwal operasional cabang dan pengecualian tanggal · WIB')
@section('content')
@php
    $hours = collect($branch->opening_hours ?? array_map(fn ($day) => ['day'=>$day, 'open'=>$day < 7, 'from'=>'08:00', 'to'=>'18:00'], range(1,7)))->keyBy('day');
    $days = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
    $minutes = $hours->filter(fn ($row) => $row['open'])->sum(fn ($row) => \Carbon\Carbon::createFromFormat('H:i', $row['from'])->diffInMinutes(\Carbon\Carbon::createFromFormat('H:i', $row['to'])));
    $exceptions = old('opening_exceptions', $branch->opening_exceptions ?? []);
    $exceptions[] = ['date'=>'','open'=>false,'from'=>'08:00','to'=>'18:00','note'=>''];
@endphp
<div class="stats-grid">
    <x-workspace-stat label="Hari operasional" :value="$hours->where('open', true)->count().' hari'" note="Dalam satu minggu" icon="imgIconClock" tone="green" />
    <x-workspace-stat label="Jam mingguan" :value="\App\Support\Workspace::number($minutes / 60, 1).' jam'" note="Jadwal reguler" icon="imgIconClock1" tone="teal" />
    <x-workspace-stat label="Hari tutup" :value="$hours->where('open', false)->count().' hari'" note="Dalam jadwal reguler" icon="imgIconClock2" tone="orange" />
    <x-workspace-stat label="Pengecualian" :value="count($branch->opening_exceptions ?? [])" note="Tanggal khusus tersimpan" icon="imgIconClock3" />
</div>
<div class="settings-top">
    <span class="badge">{{ $branch->name }} · Asia/Jakarta (WIB)</span>
    <span class="badge badge-success">{{ app(\App\Services\BranchHours::class)->status($branch) }}</span>
</div>
<form action="{{ route('hours.update') }}" method="POST" class="two-column">@csrf<div class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Informasi utama</h2>
                <small>Jadwal mingguan</small>
            </div>
            <div class="stack">@foreach($days as $day=>$label)@php($row = $hours->get($day, ['open'=>false,'from'=>'08:00','to'=>'18:00']))<div class="form-grid hours-row">
                    <strong>{{ $label }}</strong>
                    <input type="hidden" name="opening_hours[{{ $loop->index }}][day]" value="{{ $day }}">
                    <div class="form-group">
                        <label>Buka<input type="time" name="opening_hours[{{ $loop->index }}][from]" value="{{ old('opening_hours.'.$loop->index.'.from', $row['from']) }}" required>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Tutup<input type="time" name="opening_hours[{{ $loop->index }}][to]" value="{{ old('opening_hours.'.$loop->index.'.to', $row['to']) }}" required>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Status<span class="select-field">
                                <select name="opening_hours[{{ $loop->index }}][open]">
                                    <option value="1" @selected((bool) old('opening_hours.'.$loop->index.'.open', $row['open']))>Buka</option>
                                    <option value="0" @selected(!(bool) old('opening_hours.'.$loop->index.'.open', $row['open']))>Tutup</option>
                                </select>
                                <x-figma-icon name="imgIconChevron" />
                            </span>
                        </label>
                    </div>
                </div>@endforeach</div>
        </section>
        <section class="card">
            <h2>Preferensi</h2>
            <x-workspace-switch name="operational_preferences[show_open_status]" label="Tampilkan status buka" note="Status operasional ditampilkan pada nota digital." :checked="old('operational_preferences.show_open_status', $branch->operational_preferences['show_open_status'] ?? true)" icon="imgIconSemantic" />
            <x-workspace-switch name="operational_preferences[use_exceptions]" label="Gunakan jam khusus" note="Tanggal khusus menggantikan jadwal mingguan pada hari tersebut." :checked="old('operational_preferences.use_exceptions', $branch->operational_preferences['use_exceptions'] ?? true)" icon="imgIconSemantic1" />
            <div class="preference">
                <span class="stat-icon">
                    <x-figma-icon name="imgIconSemantic2" />
                </span>
                <span>
                    <strong>Pengingat operasional</strong>
                    <small>Kasir memeriksa jadwal dan mengirim pesan pelanggan secara manual dari aplikasi Android.</small>
                </span>
            </div>
        </section>
    </div>
    <aside class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Pengecualian tanggal</h2>
                <small>Jam khusus</small>
            </div>
            <x-workspace-markers :first="$branch->name.' · WIB'" :second="'Tanggal khusus disimpan sebagai pengecualian jadwal.'" />
            <p class="form-help">Kosongkan tanggal untuk menghapus baris. Simpan untuk menambahkan tanggal berikutnya.</p>@foreach($exceptions as $index=>$exception)<div class="stack" style="padding:12px 0;border-bottom:1px solid var(--border)">
                <div class="form-group">
                    <label>Tanggal<input type="date" name="opening_exceptions[{{ $index }}][date]" value="{{ $exception['date'] }}">
                    </label>
                </div>
                <div class="form-grid">
                    <label>Buka<input type="time" name="opening_exceptions[{{ $index }}][from]" value="{{ $exception['from'] }}" required>
                    </label>
                    <label>Tutup<input type="time" name="opening_exceptions[{{ $index }}][to]" value="{{ $exception['to'] }}" required>
                    </label>
                </div>
                <label>Status<span class="select-field">
                        <select name="opening_exceptions[{{ $index }}][open]">
                            <option value="0" @selected(!$exception['open'])>Tutup</option>
                            <option value="1" @selected($exception['open'])>Buka khusus</option>
                        </select>
                        <x-figma-icon name="imgIconChevron" />
                    </span>
                </label>
                <label>Catatan<input name="opening_exceptions[{{ $index }}][note]" maxlength="100" value="{{ $exception['note'] ?? '' }}">
                </label>
            </div>@endforeach<button class="btn btn-primary" style="margin-top:14px">
                <x-figma-icon name="imgIconSave" />Simpan perubahan</button>
        </section>
        <div class="notice">
            <x-figma-icon name="imgIconInfo" />Jadwal ini digunakan untuk status buka toko dan penilaian waktu presensi. Seluruh waktu menggunakan WIB.</div>
    </aside>
</form>
@endsection

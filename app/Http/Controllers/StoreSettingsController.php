<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use App\Services\StoreConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingsController extends Controller
{
    public function edit(StoreConfiguration $configuration)
    {
        return view('dashboard.settings', ['store' => $configuration->read()]);
    }

    public function update(Request $request, StoreConfiguration $configuration)
    {
        abort_unless($request->user()->role === 'owner', 403);
        $configuration->save($request);
        if ($request->is('api/*')) {
            return response()->json(['status' => 'success', 'store' => $configuration->read(true)]);
        }
        return redirect()->route('settings.edit')->with('success', 'Pengaturan toko berhasil disimpan.');
    }

    public function logo()
    {
        $path = StoreSetting::find(1)?->logo_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path, null, ['Content-Type' => 'image/png', 'Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff']);
    }
}

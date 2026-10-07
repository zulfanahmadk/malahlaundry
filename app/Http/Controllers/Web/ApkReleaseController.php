<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ApkManifest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApkReleaseController extends Controller
{
    public function index()
    {
        return view('admin.apk', ['releases' => DB::table('apk_releases')->orderByDesc('id')->paginate(20)]);
    }

    public function ownerIndex()
    {
        $release = DB::table('apk_releases')->where('package_name', 'com.malahlaundry.app')->orderByDesc('version_code')->first();
        $downloadAvailable = $release && Storage::disk('local')->exists($release->path);
        return view('dashboard.apk', compact('release', 'downloadAvailable'));
    }

    public function ownerDownload(int $id)
    {
        abort_unless(DB::table('apk_releases')->where('id', $id)->where('package_name', 'com.malahlaundry.app')->exists(), 404);
        return $this->download($id);
    }

    public function upload(Request $request, ApkManifest $manifest)
    {
        $request->validate(['apk' => 'required|file|max:102400', 'notes' => 'nullable|string|max:4000']);
        $file = $request->file('apk');
        if (strtolower($file->getClientOriginalExtension()) !== 'apk') {
            throw ValidationException::withMessages(['apk' => 'Pilih file APK.']);
        }
        $identity = $manifest->read($file->getRealPath());
        $path = $file->store('apk-releases', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['apk' => 'APK gagal disimpan. Coba kembali.']);
        }
        try {
            DB::transaction(function () use ($request, $identity, $file, $path) {
                DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
                $latest = DB::table('apk_releases')->where('package_name', $identity['package_name'])->max('version_code');
                if ($latest && $identity['version_code'] <= $latest) {
                    throw ValidationException::withMessages(['apk' => 'Version code APK harus lebih tinggi daripada versi yang sudah diterbitkan.']);
                }
                DB::table('apk_releases')->insert($identity + [
                    'path' => $path, 'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'notes' => $request->input('notes'), 'uploaded_by' => $request->user()->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        return back()->with('success', 'APK diterbitkan. Android akan menampilkan pembaruan jika version code lebih tinggi.');
    }

    public function latest(Request $request)
    {
        $data = $request->validate(['package' => ['required', Rule::in(['com.malahlaundry.app', 'com.malahlaundry.app.qa'])]]);
        $release = DB::table('apk_releases')->where('package_name', $data['package'])->orderByDesc('version_code')->first();
        return response()->json(['release' => $release ? [
            'package_name' => $release->package_name, 'version_code' => $release->version_code,
            'version_name' => $release->version_name, 'size' => $release->size,
            'sha256' => $release->sha256, 'notes' => $release->notes,
            'download_path' => '/api/v1/app-releases/'.$release->id.'/download',
        ] : null])->header('Cache-Control', 'no-store');
    }

    public function download(int $id)
    {
        $release = DB::table('apk_releases')->where('id', $id)->first();
        abort_unless($release && Storage::disk('local')->exists($release->path), 404);
        return response()->download(
            Storage::disk('local')->path($release->path),
            'MalahLaundry-'.$release->version_code.'.apk',
            ['Content-Type' => 'application/vnd.android.package-archive', 'X-Content-Type-Options' => 'nosniff']
        );
    }
}

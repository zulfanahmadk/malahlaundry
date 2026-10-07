<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile');
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string', 'password' => 'required|string|min:12|max:72|confirmed']);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Password saat ini tidak sesuai.']);
        }
        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        Storage::disk('local')->delete('admin-initial-password.txt');
        return back()->with('success', 'Password admin diperbarui.');
    }

}

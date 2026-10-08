<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;

class LoginHistory
{
    public function record(Request $request, User $user): UserLogin
    {
        $device = $request->is('api/*') ? $request->input('device_name', 'Android') : $request->userAgent();
        $device = is_string($device) ? $device : 'Tidak diketahui';
        return UserLogin::create([
            'user_id' => $user->id,
            'channel' => $request->is('api/*') ? 'android' : 'web',
            'ip_address' => $request->ip(),
            'device' => mb_substr($device, 0, 255),
            'user_agent' => mb_substr($request->userAgent() ?? '', 0, 2000),
            'created_at' => now(),
        ]);
    }

    public function location(Request $request)
    {
        $data = $request->validate([
            'login_id' => 'required|integer',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
        $login = UserLogin::whereKey($data['login_id'])->where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subMinutes(15))->firstOrFail();
        $login->update(['latitude' => $data['latitude'], 'longitude' => $data['longitude'], 'location_at' => now()]);
        return response()->json(['status' => 'success']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|max:72|confirmed',
        ]);
        $sensitive = $data['username'] !== $user->username || ! empty($data['password']);
        if ($sensitive && ! Hash::check($data['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Password saat ini tidak sesuai.']);
        }
        $user->fill(['name' => $data['name'], 'username' => $data['username']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        // Keep this device's token so its offline outbox remains usable.
        if ($sensitive) {
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
        }
        return response()->json(['status' => 'success', 'data' => ['user' => $user->only(['id', 'name', 'username', 'role', 'active', 'branch_id'])]]);
    }

    /**
     * Login endpoint untuk aplikasi Android kasir/owner.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:72',
            'device_name' => 'sometimes|required|string|max:255',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah.',
            ], 401);
        }

        if ($user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Akun admin sistem hanya digunakan di web.'], 403);
        }

        if (!$user->active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun pengguna ini dinonaktifkan.',
            ], 403);
        }

        $login = app(\App\Services\LoginHistory::class)->record($request, $user);
        $deviceName = $request->input('device_name', 'Android-Device');
        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($deviceName)->plainTextToken;
        $request->attributes->set('audit_actor', $user->only(['id', 'role']));

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'login_id' => $login->id,
                'permissions' => \App\Support\Access::permissions($user),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                    'active' => $user->active,
                    'branch_id' => $user->branch_id,
                    'branch_scoped' => (bool) $user->accessRole?->branch_id,
                    'permissions' => \App\Support\Access::permissions($user),
                ],
            ],
        ]);
    }

    /**
     * Dapatkan data user saat ini.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => array_merge($request->user()->only(['id', 'name', 'username', 'role', 'active', 'branch_id']), ['permissions' => \App\Support\Access::permissions($request->user())]),
            ],
        ]);
    }

    /**
     * Logout dan revoke token saat ini.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout.',
        ]);
    }
}

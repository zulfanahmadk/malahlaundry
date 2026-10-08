<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateUser
{
    public function handle(Request $request, User $user): User
    {
        abort_if($user->isAdmin(), 403, 'Akun admin sistem tidak dapat diubah oleh owner.');
        $data = $request->validate([
            'branch_id' => 'sometimes|required|exists:branches,id',
            'access_role_id' => 'nullable|integer|exists:access_roles,id',
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => 'required|in:owner,cashier',
            'active' => 'required|boolean',
            'password' => 'nullable|string|min:8|max:72',
        ]);
        if ($user->id === $request->user()->id && ($data['role'] !== 'owner' || ! $data['active'])) {
            throw ValidationException::withMessages(['role' => 'Owner tidak dapat menonaktifkan atau menurunkan peran akun sendiri.']);
        }
        \App\Support\Access::validateAssignment($request, $data, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->fill($data);
        $revoke = $user->isDirty('branch_id') || $user->isDirty('role') || $user->isDirty('access_role_id') || $user->isDirty('password') || $user->isDirty('username') || ($user->isDirty('active') && ! $user->active);
        if ($revoke) {
            $user->remember_token = \Illuminate\Support\Str::random(60);
        }
        $user->save();
        if ($revoke) {
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)
                    ->when($user->id === $request->user()->id, fn ($q) => $q->where('id', '!=', $request->session()->getId()))->delete();
            }
        }
        return $user;
    }
}

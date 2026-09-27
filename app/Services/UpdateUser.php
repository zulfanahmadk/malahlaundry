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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => 'required|in:owner,cashier',
            'active' => 'required|boolean',
            'password' => 'nullable|string|min:8|max:72',
        ]);
        if ($user->id === $request->user()->id && ($data['role'] !== 'owner' || ! $data['active'])) {
            throw ValidationException::withMessages(['role' => 'Owner tidak dapat menonaktifkan atau menurunkan peran akun sendiri.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->fill($data);
        $revoke = $user->isDirty('password') || $user->isDirty('username') || ($user->isDirty('active') && ! $user->active);
        $user->save();
        if ($revoke) {
            $user->tokens()->delete();
        }
        return $user;
    }
}

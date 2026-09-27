<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UpdateUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function update(Request $request, User $user, UpdateUser $update): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);
        $update->handle($request, $user);
        return response()->json(['status' => 'success', 'user' => $user->only(['id', 'name', 'username', 'role', 'active'])]);
    }
}

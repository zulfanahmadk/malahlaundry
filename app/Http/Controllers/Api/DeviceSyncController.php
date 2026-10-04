<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceSyncState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceSyncController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|uuid:4', 'name' => 'required|string|max:120',
            'app_version' => 'required|string|max:40',
            'pending_count' => 'required|integer|between:0,1000000',
            'failed_count' => 'required|integer|between:0,1000000',
            'last_error' => 'nullable|string|max:1000',
            'completed' => 'required|boolean',
        ]);
        $state = DeviceSyncState::firstOrNew(['device_id' => $data['device_id']]);
        $state->fill(collect($data)->except(['completed'])->all());
        $state->user_id = $request->user()->id;
        $state->last_seen_at = now();
        if ($data['completed'] && (int) $data['pending_count'] === 0 && (int) $data['failed_count'] === 0) {
            $state->last_synced_at = now();
        }
        $state->save();
        return response()->json(['status' => 'success']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $rows = Branch::query()->when(! $request->user()->isOwner() || $request->user()->accessRole?->branch_id, fn ($q) => $q->whereKey($request->user()->branch_id))->get();
        return response()->json(['data' => $rows->map(function ($branch) {
            return array_merge($branch->toArray(), [
                'active_orders' => Transaction::withoutGlobalScope('branch')->where('branch_id', $branch->id)->where('laundry_status', '!=', 'SELESAI')->count(),
                'staff_count' => User::where('branch_id', $branch->id)->count(),
            ]);
        })]);
    }

    public function save(Request $request, ?Branch $branch = null)
    {
        abort_unless($request->user()->isOwner(), 403);
        if ($scope = $request->user()->accessRole?->branch_id) {
            abort_unless((int) $branch?->id === (int) $scope, 403, 'Role Anda hanya dapat mengubah cabang penempatan.');
        }
        $data = $request->validate([
            'name' => ($branch?->exists ? 'sometimes|' : '').'required|string|max:100',
            'code' => [...($branch?->exists ? ['sometimes'] : []), 'required', 'string', 'max:30', Rule::unique('branches')->ignore($branch?->id)],
            'store_name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:40', 'address' => 'nullable|string|max:1000',
            'active' => 'sometimes|boolean', 'show_branch' => 'sometimes|boolean',
            'receipt_terms' => 'nullable|string|max:5000', 'complaint_days' => 'sometimes|integer|min:0|max:365',
            'opening_hours' => 'sometimes|nullable|array|size:7', 'opening_hours.*' => 'array:day,open,from,to',
            'opening_hours.*.day' => 'required|integer|between:1,7|distinct',
            'opening_hours.*.open' => 'required|boolean',
            'opening_hours.*.from' => 'required|date_format:H:i', 'opening_hours.*.to' => 'required|date_format:H:i',
            'templates' => 'sometimes|nullable|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_SELESAI,WA_REMINDER',
            'templates.*' => 'required|string|max:4000',
            'message_preferences' => 'sometimes|nullable|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_REMINDER',
            'message_preferences.*' => 'required|boolean',
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90', 'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'radius_meters' => 'sometimes|integer|between:10,10000',
            'logo_base64' => 'sometimes|nullable|string|max:1400000',
        ]);
        foreach (['opening_hours' => 'hours', 'templates' => 'templates', 'message_preferences' => 'templates'] as $field => $feature) {
            if (array_key_exists($field, $data) && $data[$field] != $branch?->$field) {
                abort_unless($request->user()->canAccess($feature, 'write'), 403, 'Role Anda tidak dapat mengubah fitur '.$feature.'.');
            }
        }
        if (array_key_exists('logo_base64', $data)) {
            $data['logo_data'] = app(\App\Services\BranchLogo::class)->normalize($data['logo_base64']);
            unset($data['logo_base64']);
        }
        foreach ($data['opening_hours'] ?? [] as $hours) {
            if ($hours['open'] && $hours['from'] >= $hours['to']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['opening_hours' => 'Jam tutup harus setelah jam buka.']);
            }
        }
        if (isset($data['opening_hours'])) {
            $data['opening_hours'] = array_map(fn ($row) => array_replace($row, ['day' => (int) $row['day'], 'open' => (bool) $row['open']]), $data['opening_hours']);
        }
        if (isset($data['message_preferences'])) {
            $data['message_preferences'] = array_map(fn ($value) => (bool) $value, $data['message_preferences']);
        }
        $branch ??= new Branch();
        $branch->fill($data)->save();
        return response()->json(['status' => 'success', 'data' => $branch]);
    }
}

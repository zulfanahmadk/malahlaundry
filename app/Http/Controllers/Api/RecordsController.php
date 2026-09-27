<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:customers,transactions',
            'after' => 'nullable|uuid:4',
        ]);
        $query = $data['type'] === 'customers' ? Customer::query() : Transaction::with(['customer', 'items.service']);
        // All active accounts can read the store's history, independently of the creator.
        $records = $query->when($data['after'] ?? null, fn ($q, $after) => $q->where('uuid', '>', strtolower($after)))
            ->orderBy('uuid')->limit(201)->get();
        $hasMore = $records->count() > 200;
        $page = $records->take(200);
        $values = $page->map(function ($record) use ($data) {
            if ($data['type'] === 'customers') {
                return $record->only(['uuid', 'name', 'phone', 'address', 'created_at']);
            }
            return [
                'uuid' => $record->uuid, 'customer_uuid' => $record->customer_uuid,
                'customer_name' => $record->customer?->name ?? 'Pelanggan',
                'customer_phone' => $record->customer?->phone ?? '',
                'user_id' => $record->user_id, 'transaction_number' => $record->transaction_number,
                'total' => $record->total, 'payment_status' => $record->payment_status,
                'laundry_status' => $record->laundry_status, 'created_at' => $record->created_at->toIso8601String(),
                'picked_up_at' => $record->picked_up_at?->toIso8601String(),
                'items' => $record->items->map(fn ($item) => [
                    'service_uuid' => $item->service_uuid, 'service_name' => $item->service_name ?? $item->service?->name ?? 'Layanan',
                    'unit' => $item->unit ?? $item->service?->unit ?? 'kg',
                    'qty' => $item->qty, 'price' => $item->price, 'total' => $item->subtotal,
                ])->values(),
            ];
        });
        return response()->json(['data' => $values->values(), 'next' => $hasMore ? $page->last()->uuid : null]);
    }
}

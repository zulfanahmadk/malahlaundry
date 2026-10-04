<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPushRequest extends FormRequest
{
    public function withValidator($validator): void
    {
        // Accept harmless read-only metadata from cached records, but only return
        // fields with explicit rules to the synchronization controller.
        $validator->excludeUnvalidatedArrayKeys = true;
    }

    public function messages(): array
    {
        return [
            'array' => ':attribute harus berupa kumpulan data JSON yang valid.',
            'list' => ':attribute harus berupa daftar JSON, bukan objek tunggal.',
            'required' => ':attribute wajib diisi.',
            'uuid' => ':attribute harus berupa UUID transaksi/data yang valid.',
            'distinct' => ':attribute berisi nilai duplikat dalam satu pengiriman.',
            'exists' => ':attribute tidak ditemukan di server.',
            'in' => ':attribute tidak sesuai nilai yang diizinkan atau cabang aktif.',
            'date' => ':attribute harus berupa tanggal dan waktu yang valid.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'boolean' => ':attribute harus bernilai true atau false.',
            'decimal' => ':attribute hanya boleh memiliki maksimal 2 angka desimal.',
            'max.array' => ':attribute berisi terlalu banyak data dalam satu pengiriman.',
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->isOwner()
            || (empty($this->input('users')) && empty($this->input('services')));
    }

    public function rules(): array
    {
        return [
            'customers.*.notes' => 'sometimes|nullable|string|max:100',
            'customers.*.archived_at' => 'sometimes|nullable|date',
            'services.*.speed' => 'sometimes|in:REGULER,EXPRESS',
            'services.*.duration_hours' => 'sometimes|integer|between:1,8760',
            'users.*.branch_id' => 'sometimes|integer|exists:branches,id',
            'transactions.*.estimated_at' => 'sometimes|nullable|date',
            'transactions.*.ready_at' => 'sometimes|nullable|date',
            'transactions.*.paid_at' => 'sometimes|nullable|date',
            'transactions.*.payment_method' => 'sometimes|nullable|in:TUNAI,QRIS,TRANSFER',
            'transactions.*.expected_total' => 'sometimes|integer|min:0',
            'transactions.*.expected_version' => 'sometimes|integer|min:1',
            'customers.*.branch_id' => ['sometimes', 'integer', Rule::in([$this->attributes->get('branch_id')])],
            'services.*.branch_id' => ['sometimes', 'integer', Rule::in([$this->attributes->get('branch_id')])],
            'transactions.*.branch_id' => ['sometimes', 'integer', Rule::in([$this->attributes->get('branch_id')])],
            'attendances.*.branch_id' => ['sometimes', 'integer', Rule::in([$this->attributes->get('branch_id')])],
            'attendances.*.in_latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'attendances.*.in_longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'attendances.*.in_accuracy' => 'sometimes|nullable|integer|between:0,100000',
            'attendances.*.out_latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'attendances.*.out_longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'attendances.*.out_accuracy' => 'sometimes|nullable|integer|between:0,100000',

            'customers' => ['sometimes', 'array', 'list', 'max:500'],
            'customers.*' => ['array'],
            'customers.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'customers.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'customers.*.phone' => ['sometimes', 'required', 'string', 'max:30'],
            'customers.*.address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'services' => ['sometimes', 'array', 'list', 'max:500'],
            'services.*' => ['array'],
            'services.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'services.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'services.*.unit' => ['sometimes', Rule::in(['kg', 'pcs', 'm2'])],
            'services.*.price' => ['sometimes', 'integer', 'min:0', 'max:1000000000'],
            'services.*.is_active' => ['sometimes', 'boolean'],
            'users' => ['sometimes', 'array', 'list', 'max:500'],
            'users.*' => ['array'],
            'users.*.username' => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            'users.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'users.*.password' => ['sometimes', 'required', 'string', 'min:8', 'max:72'],
            'users.*.role' => ['sometimes', Rule::in(['owner', 'cashier'])],
            'users.*.active' => ['sometimes', 'boolean'],
            'attendances' => ['sometimes', 'array', 'list', 'max:500'],
            'attendances.*' => ['array'],
            'attendances.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'attendances.*.user_id' => ['sometimes', 'integer', Rule::in([$this->user()->id])],
            'attendances.*.device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attendances.*.check_in_time' => ['sometimes', 'required', 'date'],
            'attendances.*.check_out_time' => ['sometimes', 'nullable', 'date'],
            'transactions' => ['sometimes', 'array', 'list', 'max:500'],
            'transactions.*' => ['array'],
            'transactions.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'transactions.*.customer_uuid' => ['sometimes', 'required', 'uuid:4'],
            'transactions.*.user_id' => ['sometimes', 'integer', 'min:1'],
            'transactions.*.transaction_number' => ['sometimes', 'required', 'string', 'max:50'],
            'transactions.*.created_at' => ['sometimes', 'required', 'date'],
            'transactions.*.subtotal' => ['sometimes', 'integer', 'min:0'],
            'transactions.*.total' => ['sometimes', 'integer', 'min:0'],
            'transactions.*.payment_status' => ['sometimes', Rule::in(['BELUM', 'LUNAS'])],
            'transactions.*.laundry_status' => ['sometimes', Rule::in(['DITERIMA', 'SIAP_DIAMBIL', 'SELESAI'])],
            'transactions.*.picked_up_at' => ['sometimes', 'nullable', 'date'],
            'transactions.*.items' => ['sometimes', 'array', 'list', 'min:1', 'max:100'],
            'transactions.*.items.*' => ['array'],
            'transactions.*.items.*.service_name' => ['sometimes', 'string', 'max:255'],
            'transactions.*.items.*.unit' => ['sometimes', Rule::in(['kg', 'pcs', 'm2'])],
            'transactions.*.items.*.service_uuid' => ['required', 'uuid:4'],
            'transactions.*.items.*.qty' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
            'transactions.*.items.*.price' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }
}

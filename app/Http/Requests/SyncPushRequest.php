<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner()
            || (empty($this->input('users')) && empty($this->input('services')));
    }

    public function rules(): array
    {
        return [
            'customers' => ['sometimes', 'array', 'list', 'max:500'],
            'customers.*' => ['array:uuid,name,phone,address'],
            'customers.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'customers.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'customers.*.phone' => ['sometimes', 'required', 'string', 'max:30'],
            'customers.*.address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'services' => ['sometimes', 'array', 'list', 'max:500'],
            'services.*' => ['array:uuid,name,unit,price,is_active'],
            'services.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'services.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'services.*.unit' => ['sometimes', Rule::in(['kg', 'pcs'])],
            'services.*.price' => ['sometimes', 'integer', 'min:0', 'max:1000000000'],
            'services.*.is_active' => ['sometimes', 'boolean'],
            'users' => ['sometimes', 'array', 'list', 'max:500'],
            'users.*' => ['array:username,name,password,role,active'],
            'users.*.username' => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            'users.*.name' => ['sometimes', 'required', 'string', 'max:255'],
            'users.*.password' => ['sometimes', 'required', 'string', 'min:8', 'max:72'],
            'users.*.role' => ['sometimes', Rule::in(['owner', 'cashier'])],
            'users.*.active' => ['sometimes', 'boolean'],
            'attendances' => ['sometimes', 'array', 'list', 'max:500'],
            'attendances.*' => ['array:uuid,user_id,device_id,check_in_time,check_out_time'],
            'attendances.*.uuid' => ['required', 'uuid:4', 'distinct:ignore_case'],
            'attendances.*.user_id' => ['sometimes', 'integer', Rule::in([$this->user()->id])],
            'attendances.*.device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attendances.*.check_in_time' => ['sometimes', 'required', 'date'],
            'attendances.*.check_out_time' => ['sometimes', 'nullable', 'date'],
            'transactions' => ['sometimes', 'array', 'list', 'max:500'],
            'transactions.*' => ['array:uuid,customer_uuid,user_id,transaction_number,subtotal,total,payment_status,laundry_status,items,created_at,picked_up_at'],
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
            'transactions.*.items.*' => ['array:service_uuid,qty,price,service_name,unit'],
            'transactions.*.items.*.service_name' => ['sometimes', 'string', 'max:255'],
            'transactions.*.items.*.unit' => ['sometimes', Rule::in(['kg', 'pcs'])],
            'transactions.*.items.*.service_uuid' => ['required', 'uuid:4'],
            'transactions.*.items.*.qty' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
            'transactions.*.items.*.price' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }
}

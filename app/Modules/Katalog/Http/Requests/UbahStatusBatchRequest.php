<?php

namespace App\Modules\Katalog\Http\Requests;

use App\Shared\Enums\StatusBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR 10: ubah status batch. Hanya Admin. Batch tidak dihapus; statusnya
 * berubah sehingga histori tersimpan.
 */
class UbahStatusBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-operations');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(StatusBatch::class)]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status batch.',
            'status.enum' => 'Status batch harus Planned, Ongoing, Completed, atau Cancelled.',
        ];
    }
}

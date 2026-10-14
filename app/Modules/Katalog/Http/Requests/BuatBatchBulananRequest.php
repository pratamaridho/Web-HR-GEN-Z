<?php

namespace App\Modules\Katalog\Http\Requests;

use App\Shared\Enums\Mode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Buat batch semua skema untuk satu periode. Hanya Admin.
 */
class BuatBatchBulananRequest extends FormRequest
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
        return [
            'periode' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'mode' => ['required', Rule::enum(Mode::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode.required' => 'Pilih bulan batch.',
            'periode.regex' => 'Periode harus berformat tahun-bulan (YYYY-MM) dengan bulan 01 sampai 12.',
            'mode.required' => 'Pilih mode Online atau Offline.',
            'mode.enum' => 'Mode harus Online atau Offline.',
        ];
    }
}

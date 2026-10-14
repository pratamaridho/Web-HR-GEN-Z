<?php

namespace App\Modules\Katalog\Http\Requests;

use App\Shared\Enums\Mode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR 04: input pembuatan batch. Hanya Admin.
 */
class BuatBatchRequest extends FormRequest
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
            'id_skema' => ['required', 'integer', 'exists:tbl_skema,id_skema'],
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
            'id_skema.required' => 'Pilih skema.',
            'id_skema.exists' => 'Skema yang dipilih tidak ada di master skema.',
            'periode.required' => 'Isi periode dengan format tahun-bulan, contoh 2026-11.',
            'periode.regex' => 'Periode harus berformat tahun-bulan (YYYY-MM) dengan bulan 01 sampai 12.',
            'mode.required' => 'Pilih mode Online atau Offline.',
            'mode.enum' => 'Mode harus Online atau Offline.',
        ];
    }
}

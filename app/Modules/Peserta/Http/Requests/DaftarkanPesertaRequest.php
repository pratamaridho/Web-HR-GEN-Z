<?php

namespace App\Modules\Peserta\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FR 06: daftarkan satu atau lebih peserta ke satu batch. Hanya Admin.
 */
class DaftarkanPesertaRequest extends FormRequest
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
            'id_peserta' => ['required', 'array', 'min:1'],
            'id_peserta.*' => ['integer', 'distinct', 'exists:tbl_peserta,id_peserta'],
            'id_batch' => ['required', 'integer', 'exists:tbl_batch,id_batch'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_peserta.required' => 'Pilih minimal satu peserta.',
            'id_peserta.min' => 'Pilih minimal satu peserta.',
            'id_peserta.*.exists' => 'Peserta yang dipilih tidak ada.',
            'id_batch.required' => 'Pilih batch.',
            'id_batch.exists' => 'Batch yang dipilih tidak ada.',
        ];
    }
}

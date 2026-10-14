<?php

namespace App\Modules\Peserta\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tempel banyak peserta sekaligus. Hanya Admin.
 */
class ImporPesertaRequest extends FormRequest
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
            'tempel' => ['required', 'string', 'max:20000'],
            'id_batch' => ['nullable', 'integer', 'exists:tbl_batch,id_batch'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tempel.required' => 'Tempel minimal satu baris dengan format "Nama, email".',
            'tempel.max' => 'Teks terlalu panjang. Bagi menjadi beberapa kali tempel.',
            'id_batch.exists' => 'Batch yang dipilih tidak ada.',
        ];
    }
}

<?php

namespace App\Modules\Peserta\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidasiPesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id_batch' => ['required', 'integer', 'exists:tbl_batch,id_batch'],
            'id_peserta' => ['nullable', 'array'],
            'id_peserta.*' => ['integer', 'exists:tbl_peserta,id_peserta'],
        ];
    }
}

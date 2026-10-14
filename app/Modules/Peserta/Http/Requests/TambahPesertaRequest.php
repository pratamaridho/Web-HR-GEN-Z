<?php

namespace App\Modules\Peserta\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FR 05: tambah peserta (nama dan email saja, minimisasi data). Hanya Admin.
 * Keunikan email diperiksa di controller agar pesan menyebut pemilik email.
 */
class TambahPesertaRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'id_batch' => ['nullable', 'integer', 'exists:tbl_batch,id_batch'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Isi nama peserta.',
            'nama.max' => 'Nama peserta maksimal 50 karakter.',
            'email.required' => 'Isi email peserta.',
            'email.email' => 'Format email belum benar.',
            'email.max' => 'Email maksimal 100 karakter.',
        ];
    }
}

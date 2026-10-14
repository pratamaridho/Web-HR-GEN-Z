<?php

namespace App\Modules\Katalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tambah trainer. Hanya Admin.
 */
class SimpanTrainerRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:50', 'unique:tbl_trainer,nama'],
            'kompetensi' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama trainer wajib diisi.',
            'nama.unique' => 'Trainer dengan nama ini sudah ada. Gunakan nama lain atau tambahkan pembeda.',
            'kompetensi.required' => 'Isi kompetensi trainer, misalnya skema yang dikuasai.',
            'kompetensi.max' => 'Kompetensi terlalu panjang. Ringkas sampai 100 karakter.',
        ];
    }
}

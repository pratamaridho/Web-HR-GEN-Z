<?php

namespace App\Modules\Kehadiran\Http\Requests;

use App\Modules\Kehadiran\Models\Kehadiran;
use App\Shared\Enums\StatusKehadiran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR 12: koreksi kehadiran oleh Admin (KehadiranPolicy::koreksi).
 */
class KoreksiKehadiranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('koreksi', Kehadiran::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kehadiran' => ['required', 'array', 'min:1'],
            'kehadiran.*' => ['required', Rule::enum(StatusKehadiran::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kehadiran.required' => 'Pilih status kehadiran untuk minimal satu sesi.',
            'kehadiran.min' => 'Pilih status kehadiran untuk minimal satu sesi.',
            'kehadiran.*.enum' => 'Status kehadiran harus Hadir, Izin, atau Alpa.',
        ];
    }
}

<?php

namespace App\Modules\Kehadiran\Http\Requests;

use App\Modules\Kehadiran\Models\Kehadiran;
use App\Shared\Enums\StatusKehadiran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR 11: Trainer mencatat Hadir, Izin, atau Alpa untuk peserta pada sesinya
 * sendiri (Policy KehadiranPolicy::input).
 */
class CatatKehadiranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('input', [Kehadiran::class, $this->route('sesi')]);
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
            'kehadiran.required' => 'Pilih status kehadiran untuk minimal satu peserta.',
            'kehadiran.min' => 'Pilih status kehadiran untuk minimal satu peserta.',
            'kehadiran.*.required' => 'Pilih Hadir, Izin, atau Alpa.',
            'kehadiran.*.enum' => 'Status kehadiran harus Hadir, Izin, atau Alpa.',
        ];
    }
}

<?php

namespace App\Modules\Penjadwalan\Http\Requests;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Penjadwalan\Actions\HitungDurasiSesi;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Penjadwalan\Rules\CekBentrokRoom;
use App\Modules\Penjadwalan\Rules\CekBentrokTrainer;
use App\Modules\Penjadwalan\Rules\CekBulanBatch;
use App\Modules\Penjadwalan\Rules\CekModeBatch;
use App\Shared\Enums\StatusSesi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR 07 dan FR 08: simpan atau ubah sesi. Hanya Admin. Pemeriksaan konflik
 * trainer, room, dan mode batch berjalan di sini; sesi yang ditolak tidak
 * tersimpan.
 */
class SimpanSesiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-operations');
    }

    /**
     * Jam dinormalkan ke H:i:s agar sama dengan format penyimpanan.
     */
    protected function prepareForValidation(): void
    {
        foreach (['jam_mulai', 'jam_selesai'] as $kolom) {
            $nilai = (string) $this->input($kolom);

            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $nilai)) {
                $this->merge([$kolom => substr($nilai, 0, 5)]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kecuali = $this->route('sesi') instanceof Sesi ? (int) $this->route('sesi')->id_sesi : null;
        $tanggal = (string) $this->input('tanggal');
        $jamMulai = $this->input('jam_mulai').':00';
        $jamSelesai = $this->input('jam_selesai').':00';

        return [
            'nama_sesi' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date_format:Y-m-d', new CekBulanBatch((array) $this->input('id_batch'))],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'id_trainer' => [
                'required', 'integer', 'exists:tbl_trainer,id_trainer',
                new CekBentrokTrainer($this->integer('id_trainer'), $tanggal, $jamMulai, $jamSelesai, $kecuali),
            ],
            'room' => [
                'required', Rule::in(config('hrgz.rooms')),
                new CekBentrokRoom((string) $this->input('room'), $tanggal, $jamMulai, $jamSelesai, $kecuali),
            ],
            'status' => ['nullable', Rule::in([StatusSesi::Planned->value, StatusSesi::Cancelled->value])],
            'catatan' => ['nullable', 'string', 'max:255'],
            'id_batch' => ['required', 'array', 'min:1', new CekModeBatch((array) $this->input('id_batch'))],
            'id_batch.*' => ['integer', 'distinct', 'exists:tbl_batch,id_batch'],
        ];
    }

    /**
     * Atribut kolom tbl_sesi dari input yang sudah divalidasi.
     *
     * @return array<string, mixed>
     */
    public function atributSesi(): array
    {
        $data = $this->validated();
        $mode = (string) Batch::whereIn('id_batch', $this->idBatch())->value('mode');

        $atribut = [
            'id_trainer' => (int) $data['id_trainer'],
            'nama_sesi' => $data['nama_sesi'],
            'tanggal' => $data['tanggal'],
            'jam_mulai' => $data['jam_mulai'].':00',
            'jam_selesai' => $data['jam_selesai'].':00',
            'durasi_hari' => (new HitungDurasiSesi)->execute($mode, $data['jam_mulai'], $data['jam_selesai']),
            'room' => $data['room'],
            'catatan' => ($data['catatan'] ?? null) ?: null,
        ];

        // Status hanya berubah bila dikirim (Planned atau Cancelled).
        if (! empty($data['status'])) {
            $atribut['status'] = $data['status'];
        }

        return $atribut;
    }

    /**
     * @return array<int>
     */
    public function idBatch(): array
    {
        return array_map('intval', $this->validated()['id_batch']);
    }
}

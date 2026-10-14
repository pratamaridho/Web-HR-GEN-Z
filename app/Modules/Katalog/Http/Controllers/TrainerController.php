<?php

namespace App\Modules\Katalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Http\Requests\SimpanTrainerRequest;
use App\Modules\Katalog\Models\Trainer;
use Illuminate\Http\RedirectResponse;

class TrainerController extends Controller
{
    public function simpan(SimpanTrainerRequest $request): RedirectResponse
    {
        $trainer = Trainer::create($request->validated());

        return redirect()->route('data-master', ['tab' => 'trainer'])
            ->with('sukses', "Trainer {$trainer->nama} ditambahkan.");
    }
}

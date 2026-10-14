<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Queries\RingkasanOperasional;
use Illuminate\View\View;

/**
 * FR 14: dashboard operasional untuk Admin dan Management. Hanya membaca.
 */
class DashboardController extends Controller
{
    public function __invoke(RingkasanOperasional $ringkasan): View
    {
        return view('dashboard', ['r' => $ringkasan->hitung()]);
    }
}

        <section x-show="tab === 'skema'" class="mt-4 rounded-md border border-line bg-white p-5">
            <h2 class="text-base font-bold">Penggabungan skema</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach (\App\Modules\Penjadwalan\Queries\AturanIntegrasi::DAFTAR as $aturan)
                    <li>{{ $aturan }}</li>
                @endforeach
            </ul>
        </section>

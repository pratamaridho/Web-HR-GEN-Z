@php
    // Kegagalan kredensial/throttle tampil sebagai banner; isian kosong
    // tampil sebagai pesan per kolom (sesuai state "error" dan "kosong").
    $pesanEmail = $errors->first('email');
    $banner = $pesanEmail !== '' && ! in_array($pesanEmail, ['Isi email.', 'Format email belum benar.'], true)
        ? $pesanEmail
        : null;
    $errEmail = $pesanEmail !== '' && $banner === null ? $pesanEmail : null;
    $errPassword = $errors->first('password');
@endphp

<x-guest-layout>
    <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm" novalidate>
        @csrf

        <h1 class="text-2xl font-bold text-navy">Masuk</h1>

        <div class="mt-6">
            @if ($banner)
                <div role="alert" class="mb-5 flex gap-2 rounded-md bg-badbg p-3 text-sm text-bad">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                    <span>{{ $banner }}</span>
                </div>
            @elseif (session('keluar'))
                <div role="status" class="mb-5 flex gap-2 rounded-md bg-okbg p-3 text-sm text-ok">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                    <span>Anda sudah keluar.</span>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <label class="block">
                <span class="mb-1.5 block text-sm font-medium">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@hrgz.example.test"
                       autocomplete="username" autofocus
                       @if ($errEmail) aria-invalid="true" @endif
                       class="h-10 w-full rounded-md border px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25 {{ $errEmail ? 'border-bad bg-badbg/40' : 'border-line bg-white' }}">
                @if ($errEmail)
                    <p class="mt-1.5 flex gap-1.5 text-sm text-bad">{{ $errEmail }}</p>
                @endif
            </label>

            <label class="block">
                <span class="mb-1.5 block text-sm font-medium">Password</span>
                <input type="password" name="password" autocomplete="current-password"
                       @if ($errPassword) aria-invalid="true" @endif
                       class="h-10 w-full rounded-md border px-3 text-sm outline-none focus:border-cobalt focus:ring-2 focus:ring-cobalt/25 {{ $errPassword ? 'border-bad bg-badbg/40' : 'border-line bg-white' }}">
                @if ($errPassword)
                    <p class="mt-1.5 flex gap-1.5 text-sm text-bad">{{ $errPassword }}</p>
                @endif
            </label>
        </div>

        <button type="submit"
                class="mt-6 inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-cobalt px-4 text-sm font-semibold text-white hover:bg-cobalt-dark">
            Masuk
        </button>
    </form>
</x-guest-layout>

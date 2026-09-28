<x-filament-panels::page>
    @if (auth()->user()->must_change_password)
        <div class="rounded-xl bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
            Admin telah mereset password Anda. Buat password baru sebelum menggunakan menu lain.
        </div>
    @endif

    @if (session('password_status'))
        <div class="rounded-xl bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-950 dark:text-green-200">{{ session('password_status') }}</div>
    @endif

    <x-filament::section heading="Keamanan akun" description="Password baru minimal delapan karakter dan berbeda dari password saat ini.">
        <form method="POST" action="{{ route('password.update') }}" class="grid max-w-xl gap-5">
            @csrf
            @foreach (['current_password' => 'Password Saat Ini', 'password' => 'Password Baru', 'password_confirmation' => 'Konfirmasi Password Baru'] as $name => $label)
                <label class="grid gap-2 text-sm font-medium">{{ $label }}
                    <x-filament::input.wrapper><x-filament::input type="password" :name="$name" required autocomplete="{{ $name === 'current_password' ? 'current-password' : 'new-password' }}" /></x-filament::input.wrapper>
                    @error($name)<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>
            @endforeach
            <div><x-filament::button type="submit">Simpan Password Baru</x-filament::button></div>
        </form>
    </x-filament::section>
</x-filament-panels::page>

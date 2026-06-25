<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Kami mengirim kode verifikasi 6 digit ke email kamu:') }}
        <span class="font-semibold text-gray-800">{{ $email }}</span>.
        {{ __('Masukkan kode di bawah ini untuk mengaktifkan akun. Cek folder Spam jika tidak ada di Inbox.') }}
    </div>

    @if (session('success'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 font-medium text-sm text-amber-600">
            {{ session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.otp.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Kode Verifikasi')" />
            <x-text-input id="code" class="block mt-1 w-full text-center tracking-[0.5em] text-lg font-semibold"
                          type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                          maxlength="6" placeholder="______" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verifikasi') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
        <form method="POST" action="{{ route('verification.otp.resend') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                {{ __('Kirim ulang kode') }}
            </button>
        </form>

        @if ($mustVerify)
            {{-- Akun baru wajib verifikasi: tidak ada halaman sebelumnya, jadi keluar = logout. --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Keluar') }}
                </button>
            </form>
        @else
            {{-- Verifikasi sukarela dari Profil: cukup kembali, jangan logout. --}}
            <a href="{{ route('profile.edit') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
                {{ __('Kembali ke Profil') }}
            </a>
        @endif
    </div>
</x-guest-layout>

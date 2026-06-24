<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Kami mengirim kode 6 digit ke') }}
        <span class="font-semibold text-gray-800">{{ $email }}</span>.
        {{ __('Masukkan kode dan password baru Anda. Cek folder Spam jika tidak ada di Inbox.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.otp.update') }}">
        @csrf

        <input type="hidden" name="email" value="{{ $email }}" />

        <div>
            <x-input-label for="code" :value="__('Kode Verifikasi')" />
            <x-text-input id="code" class="block mt-1 w-full text-center tracking-[0.4em] text-lg font-semibold"
                          type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                          maxlength="6" placeholder="______" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password Baru')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Ulangi Password Baru')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                          name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>

    {{-- Form "kirim ulang" terpisah agar tidak nested di dalam form utama. --}}
    <form method="POST" action="{{ route('password.email') }}" class="mt-4">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}" />
        <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">
            {{ __('Kirim ulang kode') }}
        </button>
    </form>
</x-guest-layout>

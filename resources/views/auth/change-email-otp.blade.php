<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Kami mengirim kode 6 digit ke') }}
        <span class="font-semibold text-gray-800">{{ $newEmail }}</span>.
        {{ __('Masukkan kode untuk mengonfirmasi bahwa email ini milikmu. Cek folder Spam jika tidak ada di Inbox.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('email.change.update') }}">
        @csrf

        <input type="hidden" name="new_email" value="{{ $newEmail }}" />

        <div>
            <x-input-label for="code" :value="__('Kode Konfirmasi')" />
            <x-text-input id="code" class="block mt-1 w-full text-center tracking-[0.4em] text-lg font-semibold"
                          type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                          maxlength="6" placeholder="______" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Ubah Email Sekarang') }}
            </x-primary-button>
        </div>
    </form>

    {{-- Form "kirim ulang" terpisah agar tidak nested di dalam form utama. --}}
    <form method="POST" action="{{ route('email.change.send') }}" class="mt-4">
        @csrf
        <input type="hidden" name="new_email" value="{{ $newEmail }}" />
        <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">
            {{ __('Kirim ulang kode') }}
        </button>
    </form>
</x-guest-layout>

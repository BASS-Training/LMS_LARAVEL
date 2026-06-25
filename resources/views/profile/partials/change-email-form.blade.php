<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Ubah Email') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Email kamu saat ini:') }}
            <span class="font-semibold text-gray-800">{{ auth()->user()->email }}</span>.
            {{ __('Untuk keamanan, email baru harus dikonfirmasi lewat kode yang kami kirim ke alamat itu. Email akun kamu baru berubah setelah kode benar.') }}
        </p>
    </header>

    <form method="POST" action="{{ route('email.change.send') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="new_email" :value="__('Email Baru')" />
            <x-text-input id="new_email" name="new_email" type="email" class="mt-1 block w-full"
                          :value="old('new_email')" required autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('new_email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Kirim Kode ke Email Baru') }}</x-primary-button>
        </div>
    </form>
</section>

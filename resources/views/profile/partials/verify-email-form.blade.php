@php
    $verified = auth()->user()->isEmailVerified();
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Verifikasi Email') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Verifikasi email meningkatkan keamanan akun (mis. untuk pemulihan password). Bersifat opsional untuk akun lama.') }}
        </p>
    </header>

    <div class="mt-6">
        @if ($verified)
            <div class="flex items-center gap-2 text-sm font-medium text-green-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
                {{ __('Email kamu sudah terverifikasi.') }}
            </div>
        @else
            <div class="flex items-center gap-2 text-sm font-medium text-amber-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                {{ __('Email kamu belum terverifikasi.') }}
            </div>

            <form method="POST" action="{{ route('verification.otp.start') }}" class="mt-4">
                @csrf
                <x-primary-button>
                    {{ __('Verifikasi Email Sekarang') }}
                </x-primary-button>
            </form>
        @endif
    </div>
</section>

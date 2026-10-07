<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Preferensi Email</h2>
        <p class="mt-1 text-sm text-gray-600">Atur email pengingat untuk sesi pembelajaran terjadwal.</p>
    </header>

    <form method="POST" action="{{ route('profile.email-preferences.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PATCH')
        <input type="hidden" name="learning_reminder_email_enabled" value="0">

        <label class="flex items-start gap-3">
            <input type="checkbox" name="learning_reminder_email_enabled" value="1"
                   @checked($user->learning_reminder_email_enabled)
                   class="mt-1 rounded border-gray-300 text-bass-red shadow-sm focus:ring-bass-red">
            <span>
                <span class="block text-sm font-medium text-gray-900">Pengingat jadwal pembelajaran</span>
                <span class="mt-1 block text-sm text-gray-600">Kirim email dalam 24 jam dan satu jam sebelum sesi terjadwal.</span>
            </span>
        </label>

        <p class="text-xs leading-5 text-gray-500">Pengaturan ini tidak memengaruhi email keamanan akun, pembayaran, sertifikat, dan hasil penilaian.</p>

        <div class="flex items-center gap-4">
            <x-primary-button>Simpan Preferensi</x-primary-button>
            @if (session('status') === 'email-preferences-updated')
                <p class="text-sm text-gray-600">Preferensi tersimpan.</p>
            @endif
        </div>
    </form>
</section>

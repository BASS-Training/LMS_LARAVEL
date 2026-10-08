<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="avatar" :value="__('Foto Profil')" />
            <div class="mt-2 flex items-center gap-4">
                @if ($user->avatar)
                    <img src="{{ asset('storage/'.$user->avatar) }}" alt="Foto {{ $user->name }}" class="h-20 w-20 rounded-full border-2 border-gray-200 object-cover">
                @else
                    <span class="flex h-20 w-20 items-center justify-center rounded-full bg-navy text-xl font-bold text-white">{{ Str::upper(Str::substr($user->name, 0, 2)) }}</span>
                @endif
                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full cursor-pointer text-sm text-gray-600 file:mr-4 file:cursor-pointer file:rounded-md file:border-0 file:bg-bass-red file:px-4 file:py-2 file:font-semibold file:text-white file:shadow-sm hover:file:bg-bass-red-hover">
            </div>
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, atau WebP. Maksimal 2 MB dan 4000 x 4000 piksel.</p>
            @if ($user->avatar)
                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-bass-red focus:ring-bass-red">
                    Hapus foto saat ini
                </label>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full bg-gray-100 cursor-not-allowed text-gray-500" :value="old('email', $user->email)" readonly required autocomplete="username" />
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Email tidak bisa diubah di sini. Gunakan kartu "Ubah Email" di atas agar email baru dikonfirmasi lebih dulu (aman dari salah ketik).') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bass-red">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-success">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="date_of_birth" :value="__('Tanggal Lahir')" />
            <x-text-input
                id="date_of_birth"
                name="date_of_birth"
                type="date"
                class="mt-1 block w-full"
                :value="old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d'))"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
        </div>

        <div>
            <x-input-label for="gender" :value="__('Jenis Kelamin')" />
            <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-md shadow-sm" required>
                <option value="">Pilih Jenis Kelamin</option>
                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('gender')" />
        </div>

        <div>
            <x-input-label for="institution_name" :value="__('Nama Instansi/Sekolah')" />
            <x-text-input id="institution_name" name="institution_name" type="text" class="mt-1 block w-full" :value="old('institution_name', $user->institution_name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('institution_name')" />
        </div>

        <div>
            <x-input-label for="occupation" :value="__('Pekerjaan')" />
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @php($selectedOcc = old('occupation', $user->occupation))
                @foreach ([
                    'Pelajar/Mahasiswa',
                    'PNS/ASN',
                    'TNI/Polri',
                    'Karyawan Swasta',
                    'Pegawai BUMN',
                    'Wiraswasta',
                    'Buruh/Tenaga Harian Lepas',
                    'Pedagang',
                    'Sopir/Pengemudi',
                    'Ibu Rumah Tangga',
                    'Pensiunan',
                    'Tidak Bekerja',
                    'Lainnya'
                ] as $job)
                    <label class="relative flex items-center p-2 rounded-lg border border-gray-300 cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="occupation" value="{{ $job }}" class="mr-2" {{ $selectedOcc === $job ? 'checked' : '' }} required>
                        <span class="text-sm">{{ $job }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('occupation')" />
        </div>

        @if ($user->can('manage own courses'))
            <div>
                <x-input-label for="instructor_bio" :value="__('Bio Instruktur')" />
                <p class="mt-1 text-xs text-gray-500">Profil ini ditampilkan kepada calon peserta pada detail course yang Anda ajar.</p>
                <div class="mt-2">
                    <x-forms.summernote-editor
                        id="instructor_bio"
                        name="instructor_bio"
                        :value="old('instructor_bio', $user->instructor_bio)"
                        preset="bio"
                        placeholder="Ceritakan pengalaman, keahlian, dan latar belakang Anda..."
                    />
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('instructor_bio')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

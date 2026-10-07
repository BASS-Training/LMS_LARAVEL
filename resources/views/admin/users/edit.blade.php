<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Pengguna: ') }} {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    {{-- Tampilkan error validasi jika ada --}}
                    @if ($errors->any())
                        <div class="mb-4">
                            <ul class="list-disc list-inside text-sm text-error">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Nama')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                        </div>

                        <!-- Email Address -->
                        <div class="mt-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" required />
                        </div>

                        <!-- Roles -->
                        <div class="mt-4">
                            <x-input-label for="roles" :value="__('Peran (Roles)')" />
                            
                            @foreach ($roles as $role)
                                <div class="flex items-center mt-2">
                                    <input type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->name }}"
                                           class="rounded border-gray-300 text-bass-red shadow-sm focus:ring-bass-red"
                                           @if(in_array($role->name, $user->getRoleNames()->toArray())) checked @endif>
                                    <label for="role_{{ $role->id }}" class="ml-2 text-sm text-gray-600">{{ $role->name }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <x-input-label for="avatar" :value="__('Foto Profil')" />
                            <div class="mt-2 flex items-center gap-4">
                                @if ($user->avatar)
                                    <img src="{{ asset('storage/'.$user->avatar) }}" alt="Foto {{ $user->name }}" class="h-20 w-20 rounded-full border-2 border-gray-200 object-cover">
                                @else
                                    <span class="flex h-20 w-20 items-center justify-center rounded-full bg-navy text-xl font-bold text-white">{{ Str::upper(Str::substr($user->name, 0, 2)) }}</span>
                                @endif
                                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600">
                            </div>
                            @if ($user->avatar)
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                    Hapus foto saat ini
                                </label>
                            @endif
                            @error('avatar') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4">
                            <x-input-label for="instructor_bio" :value="__('Bio Instruktur')" />
                            <p class="mt-1 text-xs text-gray-500">Diabaikan pada halaman publik jika pengguna bukan instruktur.</p>
                            <div class="mt-2">
                                <x-forms.summernote-editor id="instructor_bio" name="instructor_bio" :value="old('instructor_bio', $user->instructor_bio)" preset="bio" placeholder="Pengalaman dan keahlian instruktur..." />
                            </div>
                            @error('instructor_bio') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                        </div>


                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                                {{ __('Batal') }}
                            </a>
                            <x-primary-button>
                                {{ __('Simpan Perubahan') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tambah Pengguna Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama</label>
                            <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/50 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-600" value="{{ old('name') }}" required>
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/50 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-600" value="{{ old('email') }}" required>
                             @error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                            <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/50 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-600" required>
                             @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/50 focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-600" required>
                        </div>

                        <!-- Roles -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Peran (Roles)</label>
                            <div class="mt-2 space-y-2">
                                @foreach($roles as $role)
                                    <div class="flex items-center">
                                        <input type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->name }}"
                                               class="rounded border-gray-300 text-bass-red shadow-sm focus:ring-bass-red">
                                        <label for="role_{{ $role->id }}" class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $role->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                             @error('roles') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="avatar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foto Profil</label>
                            <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                            @error('avatar') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="instructor_bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio Instruktur</label>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Diabaikan pada halaman publik jika pengguna bukan instruktur.</p>
                            <div class="mt-2 text-gray-900">
                                <x-forms.summernote-editor id="instructor_bio" name="instructor_bio" :value="old('instructor_bio')" preset="bio" placeholder="Pengalaman dan keahlian instruktur..." />
                            </div>
                            @error('instructor_bio') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline mr-4">Batal</a>
                            <x-primary-button>
                                {{ __('Simpan Pengguna') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

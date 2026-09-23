<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Peran') }}
            </h2>
            {{-- PERBAIKAN: Arahkan ke route create yang benar --}}
            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center h-9 px-4 shrink-0 whitespace-nowrap bg-bass-red hover:bg-bass-red-hover text-white text-sm font-medium rounded-lg border border-transparent transition-colors">
                Buat Peran Baru
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                <div class="p-6 text-gray-900">

                    <div class="flex justify-between items-center mb-4">
                        <div class="text-lg font-semibold">{{ __('Daftar Role') }}</div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('admin.tools.permissions.refresh') }}" method="POST" class="contents" onsubmit="return confirm('Refresh permission cache sekarang?');">
                                @csrf
                                <button type="submit" class="inline-flex items-center h-9 px-4 shrink-0 whitespace-nowrap bg-bass-red hover:bg-bass-red-hover text-white text-sm font-medium rounded-lg border border-transparent transition-colors">Refresh Permission Cache</button>
                            </form>
                            <a href="{{ route('admin.tools.roles.export') }}" class="inline-flex items-center h-9 px-4 shrink-0 whitespace-nowrap bg-navy hover:bg-navy-light text-white text-sm font-medium rounded-lg border border-transparent transition-colors">Export Role Matrix</a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 text-sm text-success bg-success-soft rounded-lg" role="alert">{{ session('success') }}</div>
                    @endif
                     @if (session('error'))
                        <div class="mb-4 p-4 text-sm text-error bg-error-soft rounded-lg" role="alert">{{ session('error') }}</div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Peran</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hak Akses</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($roles as $role)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $role->name }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($role->permissions as $permission)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-navy/10 text-navy">{{ $permission->name }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-1">
                                                {{-- PERBAIKAN: Arahkan ke route edit yang benar --}}
                                                <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex items-center gap-1 h-7 px-2.5 shrink-0 whitespace-nowrap border border-transparent text-xs font-medium text-navy bg-info-soft rounded-lg hover:bg-navy/10 transition-colors">Edit</a>

                                                @if (!in_array($role->name, ['super-admin', 'instructor', 'participant', 'event-organizer']))
                                                    {{-- PERBAIKAN: Arahkan ke route destroy yang benar --}}
                                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="contents" onsubmit="return confirm('Apakah Anda yakin ingin menghapus peran ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center gap-1 h-7 px-2.5 shrink-0 whitespace-nowrap border border-transparent text-xs font-medium text-error bg-error-soft rounded-lg hover:bg-error/20 transition-colors">Hapus</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data peran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $roles->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

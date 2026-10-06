<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Periode Kursus
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $course->title }}</p>
            </div>
            <div class="flex space-x-3">
                @can('update', $course)
                    <a href="{{ route('course-periods.create', $course) }}"
                       class="inline-flex items-center px-4 py-2 bg-bass-red hover:bg-bass-red-hover text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Periode
                    </a>
                @endcan
                <a href="{{ route('courses.show', $course) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-success-soft border border-success/30 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-success mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-success">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($periods->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($periods as $period)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $period->name }}</h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($period->status === 'active') bg-success-soft text-success
                                        @elseif($period->status === 'upcoming') bg-info-soft text-navy
                                        @else bg-gray-100 text-gray-600 @endif">
                                        {{ ucfirst($period->status) }}
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        @if($period->start_date && $period->end_date)
                                            {{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}
                                        @else
                                            <span class="italic text-gray-400">Tanggal belum ditentukan</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $period->getParticipantCount() }}{{ $period->max_participants ? ' / ' . $period->max_participants : '' }} peserta
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $period->instructors->count() }} instructor
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                                <a href="{{ route('course-periods.show', [$course, $period]) }}"
                                   class="text-sm font-medium text-navy hover:text-bass-red transition">
                                    Lihat Detail
                                </a>
                                <div class="flex space-x-2">
                                    @can('update', $course)
                                        <a href="{{ route('course-periods.manage', [$course, $period]) }}"
                                           class="text-sm text-gray-600 hover:text-navy transition">
                                            Kelola
                                        </a>
                                        <a href="{{ route('course-periods.edit', [$course, $period]) }}"
                                           class="text-sm text-gray-600 hover:text-navy transition">
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $periods->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Periode</h3>
                    <p class="text-gray-500 mb-6">Buat periode pertama untuk mengelola jadwal kursus ini.</p>
                    @can('update', $course)
                        <a href="{{ route('course-periods.create', $course) }}"
                           class="inline-flex items-center px-4 py-2 bg-bass-red hover:bg-bass-red-hover text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah Periode
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

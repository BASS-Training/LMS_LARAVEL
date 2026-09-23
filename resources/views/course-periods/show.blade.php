<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $period->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $course->title }}</p>
            </div>
            <div class="flex space-x-3">
                @can('update', $course)
                    <a href="{{ route('course-periods.manage', [$course, $period]) }}"
                       class="inline-flex items-center px-4 py-2 bg-bass-red hover:bg-bass-red-hover text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Kelola
                    </a>
                @endcan
                <a href="{{ route('course-periods.index', $course) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Period Info Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ $period->name }}</h3>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($period->status === 'active') bg-success-soft text-success
                                @elseif($period->status === 'upcoming') bg-info-soft text-navy
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($period->status) }}
                            </span>
                            @if($period->start_date && $period->end_date)
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}
                                </span>
                            @else
                                <span class="italic text-gray-400">Tanggal belum ditentukan</span>
                            @endif
                            @if($period->max_participants)
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $period->getParticipantCount() }} / {{ $period->max_participants }} peserta
                                </span>
                            @endif
                        </div>
                        @if($period->description)
                            <p class="mt-3 text-sm text-gray-600">{{ $period->description }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-navy rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Instructor</div>
                    <div class="text-2xl font-bold">{{ $period->instructors->count() }}</div>
                </div>
                <div class="bg-success rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Peserta</div>
                    <div class="text-2xl font-bold">{{ $period->getParticipantCount() }}</div>
                </div>
                <div class="bg-bass-red rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Slot Tersedia</div>
                    <div class="text-2xl font-bold">{{ $period->getAvailableSlots() == PHP_INT_MAX ? '∞' : $period->getAvailableSlots() }}</div>
                </div>
                <div class="bg-gray-600 rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Durasi</div>
                    <div class="text-2xl font-bold">{{ $period->getDurationInDays() }} hari</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Instructors -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Instructor ({{ $period->instructors->count() }})
                        </h3>
                    </div>
                    <div class="p-6">
                        @forelse($period->instructors as $instructor)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-medium">{{ strtoupper(substr($instructor->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $instructor->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $instructor->email }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">Belum ada instructor</p>
                        @endforelse
                    </div>
                </div>

                <!-- Participants -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Peserta ({{ $period->participants->count() }})
                        </h3>
                    </div>
                    <div class="p-6 max-h-96 overflow-y-auto">
                        @forelse($period->participants as $participant)
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg mb-2">
                                <div class="w-8 h-8 bg-success rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $participant->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">Belum ada peserta</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

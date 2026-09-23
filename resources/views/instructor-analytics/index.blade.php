<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy -mx-4 -my-2 px-4 py-8 sm:px-6 lg:px-8 rounded-2xl shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-white text-3xl font-bold leading-tight">
                        {{ __('Analytics Keaktifan Instruktur') }}
                    </h2>
                    <p class="text-white/80 mt-2">
                        {{ __('Monitor aktivitas dan performa instruktur dalam memberikan feedback dan menilai essay') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Filter Tanggal -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Periode</h3>
                <form method="GET" action="{{ route('instructor-analytics.index') }}" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                    </div>
                    
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" 
                                class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Filter
                        </button>
                        <a href="{{ route('instructor-analytics.compare') }}" 
                           class="bg-navy hover:bg-navy-light text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Bandingkan Instruktur
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overall Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-navy rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Instruktur</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($overallStats['total_instructors']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-success rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Aktif (7 Hari)</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($overallStats['active_instructors']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-bass-red rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Diskusi</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($overallStats['total_discussion_replies']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gray-600 rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Essay Dinilai</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($overallStats['total_essays_graded']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-error rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Perlu Dinilai</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($overallStats['total_essays_pending']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructor Activity Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Aktivitas Instruktur ({{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }})
                </h3>
            </div>
            
            @if(count($instructorStats) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Instruktur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Periode yang Ditugaskan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Diskusi (Period)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Essay Dinilai
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Perlu Dinilai
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktivitas 7 Hari
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Aktivitas
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($instructorStats as $stat)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-navy rounded-full flex items-center justify-center">
                                                <span class="text-white font-semibold">
                                                    {{ strtoupper(substr($stat['instructor']->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $stat['instructor']->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $stat['instructor']->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="max-w-64">
                                            @if(!empty($stat['period_breakdown']) && is_array($stat['period_breakdown']) && count($stat['period_breakdown']) > 0)
                                                @foreach($stat['period_breakdown'] as $index => $breakdown)
                                                    @if($index < 2)
                                                        <div class="text-xs mb-2 p-2 bg-info-soft border-l-2 border-navy rounded">
                                                             <div class="font-medium text-navy">
                                                                 {{ Str::limit($breakdown['course']->title, 20) }}
                                                             </div>
                                                             <div class="text-navy font-semibold">
                                                                 {{ $breakdown['period']->name }}
                                                             </div>
                                                             <div class="text-xs text-gray-600 mt-1">
                                                                 {{ $breakdown['participants_count'] }} peserta |
                                                                 {{ $breakdown['activity_score'] }} aktivitas
                                                             </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if(!empty($stat['period_breakdown']) && count($stat['period_breakdown']) > 2)
                                                    <div class="text-xs text-gray-500 font-medium">
                                                        +{{ count($stat['period_breakdown']) - 2 }} periode lainnya
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-gray-400 text-xs">Tidak ada periode yang ditugaskan</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($stat['discussion_replies'] ?? 0) }}</div>
                                        @if(isset($stat['recent_discussions']) && $stat['recent_discussions'] > 0)
                                            <div class="text-xs text-green-600">+{{ $stat['recent_discussions'] }} minggu ini</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($stat['essay_graded'] ?? 0) }}</div>
                                        @if(isset($stat['recent_grading']) && $stat['recent_grading'] > 0)
                                            <div class="text-xs text-green-600">+{{ $stat['recent_grading'] }} minggu ini</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(isset($stat['essay_pending']) && $stat['essay_pending'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-error-soft text-error">
                                                {{ number_format($stat['essay_pending']) }} pending
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(isset($stat['recent_activity']) && $stat['recent_activity'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-soft text-success">
                                                {{ number_format($stat['recent_activity']) }} aktivitas
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Tidak aktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">{{ number_format($stat['total_activity'] ?? 0) }}</div>
                                            <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                                @php
                                                    $maxActivity = collect($instructorStats)->max('total_activity');
                                                    $totalActivity = $stat['total_activity'] ?? 0;
                                                    $percentage = $maxActivity > 0 ? ($totalActivity / $maxActivity) * 100 : 0;
                                                @endphp
                                                <div class="bg-bass-red h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('instructor-analytics.detail', $stat['instructor']) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}" 
                                           class="text-navy hover:text-bass-red">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data instruktur</h3>
                        <p class="text-sm text-gray-500">Belum ada instruktur yang terdaftar dalam sistem.</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Activity Level Legend -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Keterangan Tingkat Aktivitas</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm text-gray-700">Sangat Aktif (>20 aktivitas)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-500 rounded-full mr-2"></div>
                        <span class="text-sm text-gray-700">Cukup Aktif (5-20 aktivitas)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-sm text-gray-700">Kurang Aktif (<5 aktivitas)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>

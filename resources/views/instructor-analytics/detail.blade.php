<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy -mx-4 -my-2 px-4 py-8 sm:px-6 lg:px-8 rounded-2xl shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <nav class="flex text-sm text-white/80 mb-2" aria-label="Breadcrumb">
                        <a href="{{ route('instructor-analytics.index') }}" class="hover:text-white">Analytics Instruktur</a>
                        <span class="mx-2">›</span>
                        <span class="text-white">{{ $user->name }}</span>
                    </nav>
                    <h2 class="text-white text-3xl font-bold leading-tight">
                        Detail Aktivitas: {{ $user->name }}
                    </h2>
                    <p class="text-white/80 mt-2">
                        {{ __('Laporan lengkap aktivitas diskusi dan penilaian essay') }}
                    </p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('instructor-analytics.index') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Filter Section -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Filter dan Periode</h3>
                <form method="GET" action="{{ route('instructor-analytics.detail', $user) }}" class="flex flex-wrap gap-4">
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

                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter Kursus</label>
                        <select name="course_id" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                            <option value="">Semua Kursus</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Balasan Diskusi</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['discussion_replies']) }}</dd>
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
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Essay Dinilai</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['essay_graded']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-navy rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Aktivitas</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_activity']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Statistics Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Aktivitas Bulanan</h3>
                </div>
                <div class="p-6">
                    @if(!empty($monthlyStats) && count($monthlyStats) > 0)
                        <div class="space-y-3">
                            @foreach($monthlyStats as $stat)
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium text-gray-900">{{ $stat['month'] }}</div>
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                @php
                                                    $maxMonthly = collect($monthlyStats)->max('total');
                                                    $percentage = $maxMonthly > 0 ? ($stat['total'] / $maxMonthly) * 100 : 0;
                                                @endphp
                                                <div class="bg-navy h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span class="text-sm text-gray-600">{{ $stat['total'] }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $stat['discussions'] }} {{ $stat['grading'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <p>Tidak ada data untuk periode yang dipilih</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Course Statistics -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Aktivitas per Kursus</h3>
                </div>
                <div class="p-6">
                    @if(!empty($periodStats) && count($periodStats) > 0)
                        <div class="space-y-4">
                            @foreach($periodStats as $stat)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="font-medium text-gray-900 mb-2">{{ $stat['course']->title }}</div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div class="text-center">
                                            <div class="font-semibold text-bass-red">{{ $stat['discussions'] }}</div>
                                            <div class="text-gray-500">Diskusi</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-semibold text-success">{{ $stat['grading'] }}</div>
                                            <div class="text-gray-500">Dinilai</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-semibold text-navy">{{ $stat['total'] }}</div>
                                            <div class="text-gray-500">Total</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <p>Tidak ada aktivitas untuk periode yang dipilih</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Discussion Replies -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Aktivitas Diskusi Terbaru</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $discussionReplies->count() }} balasan dalam periode ini</p>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if($discussionReplies->count() > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach($discussionReplies->take(10) as $reply)
                                <li class="px-6 py-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 mb-1">
                                                {{ $reply->discussion->content->lesson->course->title }}
                                            </div>
                                            <div class="text-sm text-gray-600 mb-2">
                                                {{ Str::limit($reply->body, 100) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $reply->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($discussionReplies->count() > 10)
                            <div class="px-6 py-3 bg-gray-50 text-center">
                                <span class="text-sm text-gray-500">Dan {{ $discussionReplies->count() - 10 }} balasan lainnya...</span>
                            </div>
                        @endif
                    @else
                        <div class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                <p class="text-sm">Tidak ada aktivitas diskusi dalam periode ini</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Essay Grading -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Aktivitas Penilaian Essay</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $essayGrading->count() }} essay dinilai dalam periode ini</p>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if($essayGrading->count() > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach($essayGrading->take(10) as $grading)
                                <li class="px-6 py-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 mb-1">
                                                {{ $grading->submission->content->lesson->course->title }}
                                            </div>
                                            <div class="text-sm text-gray-600 mb-1">
                                                Peserta: {{ $grading->submission->user->name }}
                                            </div>
                                            @if($grading->question)
                                                <div class="text-sm text-gray-600 mb-1">
                                                    {{ Str::limit($grading->question->question, 60) }}
                                                </div>
                                            @endif
                                            <div class="flex items-center justify-between">
                                                <div class="text-xs text-gray-500">
                                                    {{ $grading->updated_at->diffForHumans() }}
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    @if($grading->score !== null)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-soft text-success">
                                                            Skor: {{ $grading->score }}
                                                        </span>
                                                    @endif
                                                    @if($grading->feedback)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-info-soft text-navy">
                                                            Ada Feedback
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($essayGrading->count() > 10)
                            <div class="px-6 py-3 bg-gray-50 text-center">
                                <span class="text-sm text-gray-500">Dan {{ $essayGrading->count() - 10 }} penilaian lainnya...</span>
                            </div>
                        @endif
                    @else
                        <div class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                <p class="text-sm">Tidak ada aktivitas penilaian dalam periode ini</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
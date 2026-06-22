<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Pengumpulan Studi Kasus — {{ $content->title }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-2xl overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Peserta</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nilai</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dikumpulkan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($submissions as $sub)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $sub->user->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm">
                                @php
                                    $badge = [
                                        'draft' => 'bg-gray-100 text-gray-600',
                                        'submitted' => 'bg-amber-100 text-amber-700',
                                        'graded' => 'bg-green-100 text-green-700',
                                    ][$sub->status] ?? 'bg-gray-100 text-gray-600';
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($sub->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $content->scoring_enabled ? ($sub->score ?? '—') : '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <a href="{{ route('case-studies.review', [$content, $sub]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">Tinjau & Nilai</a>
                                @if($content->allow_answer_download)
                                    <a href="{{ route('case-studies.download-submission', [$content, $sub]) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">PDF</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada pengumpulan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

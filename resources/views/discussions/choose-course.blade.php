<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900">Pilih Kursus</h2>
            <p class="text-sm text-gray-600 mt-1">Pilih kursus untuk mengelola diskusi materi.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                @if($courses->isEmpty())
                    <p class="text-center text-gray-500 py-8">Belum ada kursus yang ditugaskan kepada Anda.</p>
                @else
                    <form method="GET" x-data="{ courseUrl: '' }" :action="courseUrl" class="space-y-4">
                        <div>
                            <label for="discussion-course" class="block text-sm font-medium text-gray-700 mb-2">Kursus</label>
                            <select id="discussion-course" x-model="courseUrl" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                                <option value="" disabled>Pilih kursus</option>
                                @foreach($courses as $course)
                                    <option value="{{ route('courses.discussions.index', $course) }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" :disabled="!courseUrl" class="inline-flex items-center px-4 py-2 bg-bass-red text-white text-sm font-semibold rounded-lg hover:bg-bass-red-hover focus:outline-none focus:ring-2 focus:ring-bass-red focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            Kelola Diskusi
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

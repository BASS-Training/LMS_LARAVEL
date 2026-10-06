@extends('layouts.app')
@section('title', 'Edit Learning Path')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8"><h1 class="text-2xl font-bold text-gray-900">Edit Learning Path</h1><p class="mt-1 text-sm text-gray-500">Urutan ini bersifat rekomendasi dan tidak mengubah akses course peserta.</p><form method="POST" action="{{ route('admin.learning-paths.update', $learningPath) }}" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">@csrf @method('PUT') @include('admin.learning-paths.partials.form')</form></div>
@endsection

@extends('layouts.app')
@section('title', 'Buat Skema')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8"><h1 class="text-2xl font-bold text-gray-900">Buat Skema</h1><p class="mt-1 text-sm text-gray-500">Susun rekomendasi urutan course untuk tujuan belajar tertentu.</p><form method="POST" action="{{ route('admin.learning-paths.store') }}" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">@csrf @include('admin.learning-paths.partials.form')</form></div>
@endsection

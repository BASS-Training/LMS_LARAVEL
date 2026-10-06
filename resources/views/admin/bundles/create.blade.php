@extends('layouts.app')
@section('title', 'Buat Bundle')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><h1 class="text-2xl font-bold text-gray-900">Buat Bundle</h1><p class="mt-1 text-sm text-gray-500">Gabungkan beberapa course dalam satu harga.</p><form method="POST" action="{{ route('admin.bundles.store') }}" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">@csrf @include('admin.bundles.partials.form')</form></div>
@endsection

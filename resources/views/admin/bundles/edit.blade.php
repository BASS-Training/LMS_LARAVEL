@extends('layouts.app')
@section('title', 'Edit Bundle')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"><h1 class="text-2xl font-bold text-gray-900">Edit Bundle</h1><p class="mt-1 text-sm text-gray-500">Perubahan membership hanya berlaku untuk checkout baru.</p><form method="POST" action="{{ route('admin.bundles.update', $bundle) }}" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">@csrf @method('PUT') @include('admin.bundles.partials.form')</form></div>
@endsection

@extends('layouts.app')

@section('title', 'Buat Kupon')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.coupons.index') }}" class="text-sm font-semibold text-gray-500 hover:text-navy">&larr; Kembali ke manajemen kupon</a>
    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
        <h1 class="text-2xl font-bold text-gray-900">Buat Kupon</h1>
        <p class="mt-1 text-sm text-gray-500">Kupon dapat disiapkan meskipun fitur checkout masih nonaktif.</p>
        <form method="POST" action="{{ route('admin.coupons.store') }}" class="mt-7">
            @csrf
            @include('admin.coupons.partials.form', ['submitLabel' => 'Simpan Kupon'])
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Edit Kupon ' . $coupon->code)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.coupons.index') }}" class="text-sm font-semibold text-gray-500 hover:text-navy">&larr; Kembali ke manajemen kupon</a>
    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
        <h1 class="text-2xl font-bold text-gray-900">Edit Kupon {{ $coupon->code }}</h1>
        <p class="mt-1 text-sm text-gray-500">Perubahan tidak mengubah harga order yang sudah terbentuk.</p>
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="mt-7">
            @csrf
            @method('PUT')
            @include('admin.coupons.partials.form', ['submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>
</div>
@endsection

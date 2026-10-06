{{-- Stepper 3 langkah proses pembelian dengan verifikasi.
     $active: 1=Bayar, 2=Verifikasi, 3=Akses --}}
@php
    $steps = [1 => 'Bayar', 2 => 'Verifikasi', 3 => 'Akses'];
@endphp
<div class="mt-6 flex items-center justify-between">
    @foreach ($steps as $i => $label)
        <div class="flex-1 flex flex-col items-center relative">
            {{-- garis penghubung ke kiri --}}
            @if ($i > 1)
                <div class="absolute top-4 right-1/2 w-full h-0.5 {{ $active >= $i ? 'bg-success' : 'bg-gray-200' }}"></div>
            @endif

            <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                @if ($active > $i) bg-success text-white
                @elseif ($active === $i) bg-bass-red text-white ring-4 ring-bass-red/30
                @else bg-gray-200 text-gray-500 @endif">
                @if ($active > $i)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                @else
                    {{ $i }}
                @endif
            </div>
            <span class="mt-2 text-xs font-medium {{ $active >= $i ? 'text-gray-900' : 'text-gray-400' }}">{{ $label }}</span>
        </div>
    @endforeach
</div>

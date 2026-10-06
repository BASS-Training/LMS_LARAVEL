{{--
    Reusable KPI/Stat card.
    Props:
      title      — label above the number
      value      — main metric (number or string)
      icon       — SVG path string for the icon
      color      — BASS color key: navy|brand|success|warning|error|info
      subtitle   — (optional) small text below value
      delay      — (optional) animation delay in ms for stagger
--}}
@props([
    'title'    => '',
    'value'    => '0',
    'icon'     => '',
    'color'    => 'navy',
    'subtitle' => null,
    'delay'    => 0,
])

@php
$colorMap = [
    'navy'    => ['border' => 'border-navy',     'bg' => 'bg-info-soft',     'icon' => 'text-navy'],
    'brand'   => ['border' => 'border-bass-red', 'bg' => 'bg-bass-red-soft', 'icon' => 'text-bass-red'],
    'success' => ['border' => 'border-success',  'bg' => 'bg-success-soft',  'icon' => 'text-success'],
    'warning' => ['border' => 'border-warning',  'bg' => 'bg-warning-soft',  'icon' => 'text-warning'],
    'error'   => ['border' => 'border-error',    'bg' => 'bg-error-soft',    'icon' => 'text-error'],
    'info'    => ['border' => 'border-info',     'bg' => 'bg-info-soft',     'icon' => 'text-info'],
];
$c = $colorMap[$color] ?? $colorMap['navy'];
@endphp

<div {{ $attributes->merge(['class' => "kpi-card border-l-4 {$c['border']} animate-fade-in-up"]) }}
     style="animation-delay: {{ $delay }}ms">
    <div class="flex items-start gap-4">
        @if($icon)
        <div class="w-11 h-11 {{ $c['bg'] }} rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
            </svg>
        </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $value }}</p>
            @if($subtitle)
            <p class="mt-1 text-xs text-gray-500">{{ $subtitle }}</p>
            @endif
            @if($slot->isNotEmpty())
            <div class="mt-1.5">{{ $slot }}</div>
            @endif
        </div>
    </div>
</div>

{{--
    Reusable KPI/Stat card.
    Props:
      title      — label above the number
      value      — main metric (number or string)
      icon       — SVG path string for the icon
      color      — Tailwind color key: indigo|emerald|purple|orange|blue|rose|amber
      subtitle   — (optional) small text below value
      delay      — (optional) animation delay in ms for stagger
--}}
@props([
    'title'    => '',
    'value'    => '0',
    'icon'     => '',
    'color'    => 'indigo',
    'subtitle' => null,
    'delay'    => 0,
])

@php
$colorMap = [
    'indigo'  => ['border' => 'border-indigo-500',  'bg' => 'bg-indigo-50',  'icon' => 'text-indigo-600'],
    'emerald' => ['border' => 'border-emerald-500', 'bg' => 'bg-emerald-50', 'icon' => 'text-emerald-600'],
    'purple'  => ['border' => 'border-purple-500',  'bg' => 'bg-purple-50',  'icon' => 'text-purple-600'],
    'orange'  => ['border' => 'border-orange-500',  'bg' => 'bg-orange-50',  'icon' => 'text-orange-600'],
    'blue'    => ['border' => 'border-blue-500',    'bg' => 'bg-blue-50',    'icon' => 'text-blue-600'],
    'rose'    => ['border' => 'border-rose-500',    'bg' => 'bg-rose-50',    'icon' => 'text-rose-600'],
    'amber'   => ['border' => 'border-amber-500',   'bg' => 'bg-amber-50',   'icon' => 'text-amber-600'],
    'teal'    => ['border' => 'border-teal-500',    'bg' => 'bg-teal-50',    'icon' => 'text-teal-600'],
];
$c = $colorMap[$color] ?? $colorMap['indigo'];
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

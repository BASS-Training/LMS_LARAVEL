<a href="{{ $href }}"
   title="{{ $label }}"
   aria-label="{{ $label }}"
   class="group relative flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold transition {{ $active ? 'bg-bass-red text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
   :class="adminSidebarCollapsed ? 'justify-center' : ''">
    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/>
    </svg>
    <span x-show="!adminSidebarCollapsed" x-transition.opacity class="min-w-0 flex-1 truncate">{{ $label }}</span>
    @if ($badge > 0)
        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-bold {{ $active ? 'bg-white text-bass-red' : 'bg-bass-gold text-navy' }}"
              :class="adminSidebarCollapsed ? 'absolute -right-1 -top-1' : ''">{{ $badge }}</span>
    @endif
</a>

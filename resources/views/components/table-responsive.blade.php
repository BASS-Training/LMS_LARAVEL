{{--
    Responsive table wrapper.
    Usage: <x-table-responsive>
               <table>...</table>
           </x-table-responsive>
--}}
<div {{ $attributes->merge(['class' => 'w-full overflow-x-auto scrollbar-thin -mx-4 px-4 sm:mx-0 sm:px-0 rounded-b-xl']) }}>
    {{ $slot }}
</div>

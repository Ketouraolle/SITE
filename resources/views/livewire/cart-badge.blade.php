<span class="contents">
    @if ($count > 0)
        @if ($variant === 'tab')
            <span class="absolute -right-2 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white">
                {{ $count > 9 ? '9+' : $count }}
            </span>
        @else
            <span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-accent-500 px-1 text-[11px] font-bold text-white ring-2 ring-white">
                {{ $count > 99 ? '99+' : $count }}
            </span>
        @endif
    @endif
</span>

@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <span class="inline-flex items-center gap-2">
            <span class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            </span>
            <span class="text-lg font-semibold tracking-tight">Notebud</span>
            <span class="inline-flex items-center rounded-full border border-emerald-300/70 bg-emerald-100/60 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200 ml-0.5">BETA</span>
        </span>
    </flux:sidebar.brand>
@else
    <flux:brand name="Notebud" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
        <span class="inline-flex items-center rounded-full border border-emerald-300/70 bg-emerald-100/60 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200 ml-2">BETA</span>
    </flux:brand>
@endif

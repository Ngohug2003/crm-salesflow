@props([
    'title',
    'description',
    'icon' => 'inbox',
])

<div {{ $attributes->class('data-list-empty') }}>
    <div class="max-w-md px-6">
        <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
            <flux:icon :name="$icon" class="size-6" />
        </span>
        <p class="mt-4 font-medium text-slate-900 dark:text-white">{{ $title }}</p>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>

        @isset($action)
            <div class="mt-4">{{ $action }}</div>
        @endisset
    </div>
</div>

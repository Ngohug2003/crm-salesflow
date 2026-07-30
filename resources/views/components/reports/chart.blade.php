@props([
    'title',
    'description',
    'config',
    'height' => 'h-72',
])

<section class="crm-card space-y-4">
    <div>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    </div>

    <div
        wire:ignore
        x-data="salesflowChart({{ Js::from($config) }})"
        data-salesflow-chart
        class="{{ $height }} relative"
    >
        <canvas
            x-ref="canvas"
            role="img"
            aria-label="{{ $title }}. {{ $description }}"
        >
            {{ $description }}
        </canvas>
    </div>
</section>

@props([
    'label' => 'Đang tải dữ liệu…',
    'target' => null,
])

<div
    wire:loading.delay
    @if ($target) wire:target="{{ $target }}" @endif
    {{ $attributes->class('data-list-loading') }}
    role="status"
    aria-live="polite"
>
    <flux:icon.arrow-path class="size-7 animate-spin text-emerald-600 dark:text-emerald-400" />
    <span class="sr-only">{{ $label }}</span>
</div>

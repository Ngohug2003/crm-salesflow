@props(['paginator'])

@if ($paginator->hasPages())
    <div {{ $attributes->class('data-list-pagination') }}>
        <flux:pagination :paginator="$paginator->onEachSide(1)" />
    </div>
@endif

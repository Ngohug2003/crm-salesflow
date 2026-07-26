@props([
    'model',
    'label' => 'Nội dung',
    'placeholder' => 'Nhập nội dung...',
    'editorKey' => 'rich-text-editor',
])

<div
    wire:key="{{ $editorKey }}"
    x-data="salesflowRichTextEditor($wire, @js($model), @js($placeholder))"
    class="space-y-1.5"
>
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
        {{ $label }}
    </label>

    <div
        wire:ignore
        class="task-rich-text overflow-hidden rounded-lg border border-slate-300 bg-white transition focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-900"
    >
        <div x-ref="editor"></div>
    </div>

    @error($model)
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

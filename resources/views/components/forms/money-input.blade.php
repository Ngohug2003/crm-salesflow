@props(['model', 'label', 'placeholder' => '0', 'required' => false])

<div
    x-data="salesflowMoneyInput($wire, '{{ $model }}')"
    x-init="init()"
    x-on:salesflow-money-input-updated.window="syncFromServer($event)"
>
    <flux:field>
        <flux:label>{{ $label }}{{ $required ? ' *' : '' }}</flux:label>
        <div class="relative">
            <input
                type="text"
                inputmode="numeric"
                data-flux-control
                x-model="display"
                x-on:input="sync($event)"
                x-on:blur="format()"
                placeholder="{{ $placeholder }}"
                {{ $attributes->merge(['class' => 'block h-10 w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 pr-10 text-base leading-[1.375rem] text-zinc-700 shadow-xs outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:text-sm dark:border-white/10 dark:bg-white/10 dark:text-zinc-300']) }}
            />
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400">VNĐ</span>
        </div>
        @error($model)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
    </flux:field>
</div>

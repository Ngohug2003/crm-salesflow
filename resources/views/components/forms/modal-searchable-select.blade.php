@props([
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'placeholder' => 'Chọn một giá trị',
    'searchPlaceholder' => 'Tìm kiếm…',
    'emptyMessage' => 'Không tìm thấy kết quả.',
    'disabled' => false,
])

@php
    $wireModelAttribute = collect(array_keys($attributes->getAttributes()))
        ->first(static fn (string $name): bool => str_starts_with($name, 'wire:model'));
    $wireModelName = $wireModelAttribute === null ? null : $attributes->get($wireModelAttribute);
    $isLiveModel = $wireModelAttribute !== null && str_contains($wireModelAttribute, '.live');

    $slotHtml = isset($slot) ? $slot->toHtml() : '';
    $parsedOptions = [];

    if (!empty($options)) {
        $optionCollection = is_array($options) ? collect($options) : $options;
        foreach ($optionCollection as $key => $item) {
            if (is_array($item)) {
                $val = $item[$optionValue] ?? $item['value'] ?? $item['id'] ?? $key;
                $lbl = $item[$optionLabel] ?? $item['name'] ?? $item['label'] ?? $val;
                $parsedOptions[] = ['value' => (string) $val, 'label' => (string) $lbl];
            } elseif (is_object($item)) {
                $val = $item->{$optionValue} ?? $item->value ?? $item->id ?? $key;
                $lbl = $item->{$optionLabel} ?? $item->name ?? $item->label ?? $val;
                $parsedOptions[] = ['value' => (string) $val, 'label' => (string) $lbl];
            } else {
                $parsedOptions[] = ['value' => (string) $key, 'label' => (string) $item];
            }
        }
    } elseif (trim($slotHtml) !== '') {
        preg_match_all('/<option\b(?P<attributes>[^>]*)>(?P<label>.*?)<\/option>/si', $slotHtml, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            preg_match('/\bvalue\s*=\s*(["\'])(?P<value>.*?)\1/si', $match['attributes'], $valMatch);
            $val = html_entity_decode($valMatch['value'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $lbl = trim(html_entity_decode(strip_tags($match['label']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $parsedOptions[] = ['value' => (string) $val, 'label' => (string) $lbl];
        }
    }
@endphp

<div
    x-data="{
        open: false,
        search: '',
        value: @if($wireModelName) $wire.entangle('{{ $wireModelName }}'){{ $isLiveModel ? '.live' : '' }} @else null @endif,
        options: {{ json_encode($parsedOptions) }},
        openUpward: false,
        optionObserver: null,

        init() {
            this.optionObserver = new MutationObserver(() => this.refreshOptions());
            this.optionObserver.observe(this.$el, {
                attributes: true,
                attributeFilter: ['data-modal-searchable-options'],
            });
        },

        destroy() {
            this.optionObserver?.disconnect();
        },

        refreshOptions() {
            try {
                this.options = JSON.parse(this.$el.dataset.modalSearchableOptions ?? '[]');
            } catch (_error) {
                this.options = [];
            }
        },

        get filteredOptions() {
            if (!this.search || !this.search.trim()) return this.options;
            let q = this.search.toLowerCase().trim();
            return this.options.filter(o => String(o.label).toLowerCase().includes(q) || String(o.value).toLowerCase().includes(q));
        },

        get selectedLabel() {
            if (this.value === null || this.value === '' || this.value === undefined) {
                return '{{ addslashes($placeholder) }}';
            }
            let found = this.options.find(o => String(o.value) === String(this.value));
            return found ? found.label : '{{ addslashes($placeholder) }}';
        },

        toggle() {
            if (this.open) { this.close(); return; }

            const triggerRect = this.$refs.trigger.getBoundingClientRect();
            const dialogRect = this.$el.closest('dialog')?.getBoundingClientRect();
            const visibleTop = dialogRect?.top ?? 0;
            const visibleBottom = dialogRect?.bottom ?? window.innerHeight;
            const availableAbove = triggerRect.top - visibleTop;
            const availableBelow = visibleBottom - triggerRect.bottom;

            this.openUpward = availableBelow < 288 && availableAbove > availableBelow;
            this.open = true;
        },

        close() {
            this.open = false;
            this.search = '';
        },

        selectOption(val) {
            this.value = val;
            this.close();
        }
    }"
    @click.outside="close()"
    @keydown.escape.window="close()"
    data-modal-searchable-options="{{ json_encode($parsedOptions) }}"
    class="relative w-full"
>
    {{-- Trigger Button --}}
    <button
        type="button"
        x-ref="trigger"
        @click.prevent.stop="toggle()"
        @if($disabled) disabled @endif
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open"
        aria-label="{{ $placeholder }}"
        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-900 shadow-xs transition-colors hover:border-slate-400 focus:border-indigo-500 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:hover:border-slate-600 dark:focus:border-indigo-400"
    >
        <span class="truncate" :class="{ 'text-slate-400 dark:text-slate-500': value === null || value === '' }">
            <span x-text="selectedLabel"></span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        style="display: none;"
        :class="openUpward ? 'bottom-full mb-1.5 origin-bottom' : 'top-full mt-1.5 origin-top'"
        class="absolute left-0 z-50 w-full max-h-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
        role="listbox"
    >
        <div class="p-2">
            <div class="relative flex items-center">
                <svg class="pointer-events-none absolute left-2.5 h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input
                    type="text"
                    x-model="search"
                    x-ref="searchInput"
                    x-effect="if (open) $nextTick(() => { if ($refs.searchInput) $refs.searchInput.focus() })"
                    placeholder="{{ $searchPlaceholder }}"
                    @click.stop
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-3 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400"
                />
            </div>
        </div>

        <div class="max-h-56 overflow-y-auto px-1.5 pb-1.5">
            <template x-for="opt in filteredOptions" :key="opt.value">
                <button
                    type="button"
                    @click.prevent.stop="selectOption(opt.value)"
                    class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-200 dark:hover:bg-indigo-950/50 dark:hover:text-indigo-400"
                    :class="{ 'bg-indigo-50 font-semibold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400': String(opt.value) === String(value) }"
                >
                    <span x-text="opt.label" class="truncate"></span>
                    <svg x-show="String(opt.value) === String(value)" class="h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </button>
            </template>

            <template x-if="filteredOptions.length === 0">
                <div class="px-3 py-4 text-center text-xs text-slate-400 dark:text-slate-500">
                    {{ $emptyMessage }}
                </div>
            </template>
        </div>
    </div>
</div>

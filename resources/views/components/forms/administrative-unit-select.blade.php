@props([
    'provinceModel',
    'wardModel',
    'provinceOptions' => [],
    'wardOptions' => [],
    'provinceId' => null,
    'provinceError' => null,
    'wardError' => null,
])

<div class="contents">
    <flux:field>
        <flux:label>Tỉnh/Thành phố</flux:label>
        <x-searchable-select
            wire:model="{{ $provinceModel }}"
            :options="$provinceOptions"
            option-label="full_name"
            placeholder="Chọn Tỉnh/Thành phố"
            search-placeholder="Tìm Tỉnh/Thành phố…"
            empty-message="Không tìm thấy Tỉnh/Thành phố."
        />
        @if ($provinceError)
            <flux:error :message="$provinceError" />
        @endif
    </flux:field>

    <div wire:loading.class="opacity-60" wire:target="{{ $provinceModel }}">
        <flux:field wire:key="administrative-ward-select-{{ str_replace('.', '-', $provinceModel) }}-{{ $provinceId ?: 'none' }}">
            <flux:label>Phường/Xã</flux:label>
            <x-searchable-select
                wire:model="{{ $wardModel }}"
                :options="$wardOptions"
                option-label="full_name"
                :placeholder="blank($provinceId) ? 'Chọn Tỉnh/Thành phố trước' : 'Chọn Phường/Xã'"
                search-placeholder="Tìm Phường/Xã…"
                empty-message="Không tìm thấy Phường/Xã."
                :disabled="blank($provinceId)"
            />
            @if ($wardError)
                <flux:error :message="$wardError" />
            @endif
        </flux:field>
    </div>
</div>

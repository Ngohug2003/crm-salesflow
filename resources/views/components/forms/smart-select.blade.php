@props([
    'label' => null,
    'placeholder' => null,
    'searchPlaceholder' => 'Tìm kiếm…',
    'emptyMessage' => 'Không tìm thấy kết quả.',
    'searchThreshold' => 5,
    'disabled' => false,
    'required' => false,
])

@php
    $slotHtml = $slot->toHtml();
    $parseOptions = static function (string $html): array {
        $options = [];

        preg_match_all(
            '/<option\b(?P<attributes>[^>]*)>(?P<label>.*?)<\/option>/si',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            $optionAttributes = $match['attributes'];
            preg_match('/\bvalue\s*=\s*(["\'])(?P<value>.*?)\1/si', $optionAttributes, $valueMatch);

            $options[] = [
                'value' => html_entity_decode($valueMatch['value'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'label' => trim(html_entity_decode(strip_tags($match['label']), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'disabled' => preg_match('/\bdisabled(?:\s|=|$)/i', $optionAttributes) === 1,
            ];
        }

        return $options;
    };

    $parsedOptions = $parseOptions($slotHtml);

    $selectableOptions = collect($parsedOptions)
        ->filter(static fn (array $option): bool => $option['value'] !== '' && ! $option['disabled'])
        ->values();

    $emptyOption = collect($parsedOptions)->firstWhere('value', '');
    $packageOptions = $selectableOptions;
    $resolvedPlaceholder = $placeholder
        ?? data_get($emptyOption, 'label')
        ?? 'Chọn một giá trị';
    $nativePlaceholder = $emptyOption === null ? $placeholder : null;

    $groupedOptions = [];
    preg_match_all(
        '/<optgroup\b(?P<attributes>[^>]*)>(?P<options>.*?)<\/optgroup>/si',
        $slotHtml,
        $groupMatches,
        PREG_SET_ORDER,
    );

    foreach ($groupMatches as $groupMatch) {
        preg_match('/\blabel\s*=\s*(["\'])(?P<label>.*?)\1/si', $groupMatch['attributes'], $labelMatch);
        $groupItems = collect($parseOptions($groupMatch['options']))
            ->filter(static fn (array $option): bool => $option['value'] !== '' && ! $option['disabled'])
            ->values()
            ->all();

        if ($groupItems !== []) {
            $groupedOptions[] = [
                'group' => html_entity_decode($labelMatch['label'] ?? 'Khác', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'items' => $groupItems,
            ];
        }
    }

    $hasGroupedOptions = $groupedOptions !== [];
    if ($hasGroupedOptions) {
        if ($emptyOption !== null) {
            array_unshift($groupedOptions, [
                'group' => 'Lựa chọn',
                'items' => [[
                    'value' => '',
                    'label' => $resolvedPlaceholder,
                    'disabled' => false,
                ]],
            ]);
        }

        $packageOptions = collect($groupedOptions);
    } elseif ($emptyOption !== null) {
        $packageOptions = collect([[
            'value' => '',
            'label' => $resolvedPlaceholder,
            'disabled' => false,
        ]])->concat($packageOptions)->values();
    }
    $modelAttribute = collect(array_keys($attributes->getAttributes()))
        ->first(static fn (string $name): bool => str_starts_with($name, 'wire:model'));
    $modelName = $modelAttribute === null ? null : $attributes->get($modelAttribute);
    $usesSearch = $modelName !== null && $selectableOptions->count() >= (int) $searchThreshold;
@endphp

@if ($usesSearch)
    <flux:field :class="$attributes->get('class')">
        @if (filled($label))
            <flux:label>{{ $label }}</flux:label>
        @endif

        <x-searchable-select
            {{ $attributes->except(['wire:key', 'wire:change']) }}
            :options="$packageOptions->all()"
            option-value="value"
            option-label="label"
            :placeholder="$resolvedPlaceholder"
            :search-placeholder="$searchPlaceholder"
            :empty-message="$emptyMessage"
            :clearable="false"
            :disabled="$disabled"
            :grouped="$hasGroupedOptions"
            group-label="group"
            group-options="items"
            :aria-label="$label ?? $resolvedPlaceholder"
        />

        @if ($modelName !== null)
            <flux:error :name="$modelName" />
        @endif
    </flux:field>
@else
    <flux:select
        {{ $attributes }}
        :label="$label"
        :placeholder="$nativePlaceholder"
        :disabled="$disabled"
        :required="$required"
    >
        {{ $slot }}
    </flux:select>
@endif

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

beforeEach(function (): void {
    View::share('errors', new ViewErrorBag);
});

it('keeps Flux select for fewer than five data options', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-forms.smart-select wire:model="status" label="Trạng thái">
            <option value="">Tất cả</option>
            <option value="new">Mới</option>
            <option value="open">Đang mở</option>
            <option value="won">Thành công</option>
            <option value="lost">Thất bại</option>
        </x-forms.smart-select>
    BLADE);

    expect($html)
        ->toContain('data-flux-select-native')
        ->not->toContain('searchableSelect({');
    expect(substr_count($html, 'Tất cả'))->toBe(1);
});

it('uses searchable select from five data options', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-forms.smart-select wire:model="ownerId" label="Người phụ trách">
            <option value="">Chưa phân công</option>
            <option value="1">Nguyễn An</option>
            <option value="2">Trần Bình</option>
            <option value="3">Lê Chi</option>
            <option value="4">Phạm Dũng</option>
            <option value="5">Hoàng Giang</option>
        </x-forms.smart-select>
    BLADE);

    expect($html)
        ->toContain('searchableSelect({')
        ->toContain('Tìm kiếm…')
        ->toContain('wireModelKey: &quot;ownerId&quot;')
        ->not->toContain('data-flux-select-native');
});

it('renders the shared dependent administrative unit selector', function (): void {
    $provinces = collect([
        (object) ['id' => 1, 'full_name' => 'Thành phố Hà Nội'],
    ]);
    $wards = collect([
        (object) ['id' => 10, 'full_name' => 'Phường Ba Đình'],
    ]);

    $html = Blade::render(<<<'BLADE'
        <div class="grid">
            <x-forms.administrative-unit-select
                province-model="form.provinceId"
                ward-model="form.wardId"
                :province-options="$provinces"
                :ward-options="$wards"
                province-id="1"
            />
        </div>
    BLADE, compact('provinces', 'wards'));

    expect(substr_count($html, 'searchableSelect({'))->toBe(2)
        ->and($html)->toContain('wireModelKey: &quot;form.provinceId&quot;')
        ->and($html)->toContain('wireModelKey: &quot;form.wardId&quot;')
        ->and($html)->toContain('Tỉnh/Thành phố')
        ->and($html)->toContain('Phường/Xã');
});

it('preserves option groups when a long select becomes searchable', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-forms.smart-select wire:model="companyId" label="Doanh nghiệp">
            <option value="">Chọn doanh nghiệp</option>
            <optgroup label="Gợi ý trùng khớp">
                <option value="1">Công ty An</option>
                <option value="2">Công ty Bình</option>
            </optgroup>
            <optgroup label="Tất cả doanh nghiệp khác">
                <option value="3">Công ty Chi</option>
                <option value="4">Công ty Dũng</option>
                <option value="5">Công ty Giang</option>
            </optgroup>
        </x-forms.smart-select>
    BLADE);

    preg_match('/data-searchable-options="(?P<options>[^"]+)"/', $html, $matches);
    $groups = json_decode(html_entity_decode($matches['options'], ENT_QUOTES | ENT_HTML5), true);

    expect($html)
        ->toContain('searchableSelect({')
        ->toContain('grouped: true');
    expect(array_column($groups, 'group'))->toBe([
        'Lựa chọn',
        'Gợi ý trùng khớp',
        'Tất cả doanh nghiệp khác',
    ]);
});

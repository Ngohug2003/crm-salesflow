<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('keeps the searchable dropdown inside the Flux dialog top layer', function (): void {
    $departments = collect([
        (object) ['id' => 1, 'name' => 'Kinh doanh'],
        (object) ['id' => 2, 'name' => 'Công nghệ thông tin'],
        (object) ['id' => 3, 'name' => 'Chăm sóc khách hàng'],
        (object) ['id' => 4, 'name' => 'Marketing'],
        (object) ['id' => 5, 'name' => 'Tài chính'],
    ]);

    $html = Blade::render(<<<'BLADE'
        <flux:modal name="staff-form-modal">
            <x-forms.modal-searchable-select
                wire:model.live="departmentId"
                :options="$departments"
                option-value="id"
                option-label="name"
                placeholder="Chưa gán phòng ban"
            />
        </flux:modal>
    BLADE, compact('departments'));

    $dialogStart = strpos($html, '<dialog');
    $dropdown = strpos($html, 'max-h-72 overflow-hidden');
    $dialogEnd = strpos($html, '</dialog>');

    expect($html)
        ->toContain('x-ref="trigger"')
        ->toContain('toggle()')
        ->toContain("entangle('departmentId').live")
        ->toContain('data-modal-searchable-options=')
        ->toContain('role="combobox"')
        ->toContain('openUpward')
        ->toContain('absolute left-0 z-50')
        ->not->toContain('dropStyle')
        ->not->toContain('fixed inset-0');
    expect($dialogStart)->not->toBeFalse()
        ->and($dropdown)->not->toBeFalse()
        ->and($dialogEnd)->not->toBeFalse()
        ->and($dropdown)->toBeGreaterThan($dialogStart)
        ->and($dropdown)->toBeLessThan($dialogEnd);
});

it('uses the modal-safe select in crm dialogs', function (): void {
    $leadWorkflow = file_get_contents(resource_path('views/livewire/leads/lead-workflow.blade.php'));
    $leadRoutingRules = file_get_contents(resource_path('views/livewire/leads/lead-routing-rules.blade.php'));
    $catalogSettings = file_get_contents(resource_path('views/livewire/products/product-catalog-settings.blade.php'));
    $opportunityLineItems = file_get_contents(resource_path('views/livewire/opportunities/opportunity-line-items.blade.php'));

    expect($leadWorkflow)
        ->toContain('<x-forms.modal-searchable-select')
        ->not->toContain('<x-searchable-select');
    expect($leadRoutingRules)
        ->toContain('<x-forms.modal-searchable-select')
        ->not->toContain('<x-searchable-select');
    expect($catalogSettings)
        ->toContain('wire:model="categoryParentId"')
        ->toContain('<x-forms.modal-searchable-select')
        ->not->toContain('<x-forms.smart-select wire:model="categoryParentId"');
    expect($opportunityLineItems)
        ->toContain('wire:model.live="productId"')
        ->toContain('search-placeholder="Tìm theo SKU hoặc tên sản phẩm…"')
        ->toContain('<x-forms.modal-searchable-select')
        ->not->toContain('<x-forms.smart-select wire:model.live="productId"');
});

<?php

declare(strict_types=1);

dataset('data table views', [
    'audit logs' => 'livewire/audit-logs/audit-log-list.blade.php',
    'companies' => 'livewire/companies/company-list.blade.php',
    'contacts' => 'livewire/contacts/contact-list.blade.php',
    'departments' => 'livewire/departments/department-management.blade.php',
    'leads' => 'livewire/leads/lead-list.blade.php',
    'lead trash' => 'livewire/leads/lead-trash.blade.php',
    'sessions' => 'livewire/settings/session-manager.blade.php',
    'users' => 'livewire/users/user-list.blade.php',
]);

dataset('data feed views', [
    'opportunities' => 'livewire/opportunities/opportunity-list.blade.php',
    'pipelines' => 'livewire/pipelines/pipeline-list.blade.php',
    'tasks' => 'livewire/tasks/task-list.blade.php',
]);

function viewMarkup(string $relativePath): string
{
    $markup = file_get_contents(dirname(__DIR__, 2)."/resources/views/{$relativePath}");

    expect($markup)->not->toBeFalse();

    return $markup;
}

it('uses the shared Flux table pattern', function (string $view): void {
    expect(viewMarkup($view))
        ->toContain('data-list-heading')
        ->toContain('data-list-content')
        ->toContain('<flux:table')
        ->toContain('<x-data-list.loading')
        ->not->toContain('<table');
})->with('data table views');

it('uses the shared list feed pattern', function (string $view): void {
    expect(viewMarkup($view))
        ->toContain('data-list-heading')
        ->toContain('data-list-content')
        ->toContain('data-list-feed')
        ->toContain('data-list-feed-item')
        ->toContain('<x-data-list.loading')
        ->toContain('<x-data-list.empty')
        ->toContain('<x-data-list.pagination');
})->with('data feed views');

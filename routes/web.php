<?php

use App\Http\Controllers\Api\SystemHealthCheckApiController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\ImportExport\ExportHistoryController;
use App\Http\Controllers\ImportExport\ImportHistoryController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\RoleGuideController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGuideController;
use App\Livewire\Companies\CompanyDetail;
use App\Livewire\Companies\CompanyEditor;
use App\Livewire\Companies\CompanyList;
use App\Livewire\Contacts\ContactDetail;
use App\Livewire\Contacts\ContactEditor;
use App\Livewire\Contacts\ContactList;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\ImportExport\ExportHistoryIndex;
use App\Livewire\ImportExport\ImportHistoryIndex;
use App\Livewire\Imports\LeadImportWizard;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Opportunities\OpportunityDetail;
use App\Livewire\Opportunities\OpportunityEditor;
use App\Livewire\Opportunities\OpportunityKanban;
use App\Livewire\Opportunities\OpportunityList;
use App\Livewire\Pipelines\PipelineDetail;
use App\Livewire\Pipelines\PipelineEditor;
use App\Livewire\Pipelines\PipelineList;
use App\Livewire\Platform\SystemConsole;
use App\Livewire\Reports\FunnelReport;
use App\Livewire\Reports\RevenueReport;
use App\Livewire\Reports\SalesPerformanceReport;
use App\Livewire\Roles\PermissionMatrixView;
use App\Livewire\Settings\SessionManager;
use App\Livewire\Tasks\TaskCalendar;
use App\Livewire\Tasks\TaskCreate;
use App\Livewire\Tasks\TaskKanban;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Tasks\TaskShow;
use App\Livewire\Users\UserSessionHistory;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'request.context.authenticated', 'verified', 'account.active'])->group(function (): void {
    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::get('/leads/trash', [LeadController::class, 'trash'])->name('leads.trash');
    Route::get('/leads/{leadId}', [LeadController::class, 'show'])->whereNumber('leadId')->name('leads.show');
    Route::get('/leads/{leadId}/edit', [LeadController::class, 'edit'])->whereNumber('leadId')->name('leads.edit');

    Route::get('/notifications', NotificationCenter::class)->name('notifications.index');
    Route::get('/imports/leads', LeadImportWizard::class)->name('imports.leads');
    Route::get('/exports/download/{batch}', ExportDownloadController::class)
        ->name('exports.download')
        ->middleware('signed');

    Route::get('/companies', CompanyList::class)->name('companies.index');
    Route::get('/companies/create', CompanyEditor::class)->name('companies.create');
    Route::get('/companies/{companyId}', CompanyDetail::class)->whereNumber('companyId')->name('companies.show');
    Route::get('/companies/{companyId}/edit', CompanyEditor::class)->whereNumber('companyId')->name('companies.edit');

    Route::get('/contacts', ContactList::class)->name('contacts.index');
    Route::get('/contacts/create', ContactEditor::class)->name('contacts.create');
    Route::get('/contacts/{contactId}', ContactDetail::class)->whereNumber('contactId')->name('contacts.show');
    Route::get('/contacts/{contactId}/edit', ContactEditor::class)->whereNumber('contactId')->name('contacts.edit');

    Route::get('/opportunities', OpportunityList::class)->name('opportunities.index');
    Route::get('/opportunities/kanban', OpportunityKanban::class)->name('opportunities.kanban');
    Route::get('/opportunities/create', OpportunityEditor::class)->name('opportunities.create');
    Route::get('/opportunities/{opportunityId}', OpportunityDetail::class)->whereNumber('opportunityId')->name('opportunities.show');
    Route::get('/opportunities/{opportunityId}/edit', OpportunityEditor::class)->whereNumber('opportunityId')->name('opportunities.edit');

    Route::get('/pipelines', PipelineList::class)->name('pipelines.index');
    Route::get('/pipelines/create', PipelineEditor::class)->name('pipelines.create');
    Route::get('/pipelines/{pipelineId}', PipelineDetail::class)->whereNumber('pipelineId')->name('pipelines.show');
    Route::get('/pipelines/{pipelineId}/edit', PipelineEditor::class)->whereNumber('pipelineId')->name('pipelines.edit');

    Route::get('/tasks', TaskList::class)->name('tasks.index');
    Route::get('/tasks/create', TaskCreate::class)->name('tasks.create');
    Route::get('/tasks/kanban', TaskKanban::class)->name('tasks.kanban');
    Route::get('/tasks/calendar', TaskCalendar::class)->name('tasks.calendar');
    Route::get('/tasks/{taskId}', TaskShow::class)->whereNumber('taskId')->name('tasks.show');

    Route::get('/reports/funnel', FunnelReport::class)->name('reports.funnel');
    Route::get('/reports/revenue', RevenueReport::class)->name('reports.revenue');
    Route::get('/reports/performance', SalesPerformanceReport::class)->name('reports.performance');

    Route::get('/settings/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/settings/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/settings/permission-matrix', PermissionMatrixView::class)->name('roles.permission-matrix');
    Route::get('/settings/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/settings/system-console', SystemConsole::class)->name('system-console.index');
    Route::get('/settings/system-console/health-api', SystemHealthCheckApiController::class)->name('system-console.health-api');
    Route::get('/settings/sessions', SessionManager::class)->name('sessions.index');
    Route::get('/imports/history', ImportHistoryIndex::class)->name('imports.history');
    Route::get('/imports/history/{batch}/error-log', [ImportHistoryController::class, 'downloadErrorLog'])->name('imports.history.download-errors');
    Route::get('/exports/history', ExportHistoryIndex::class)->name('exports.history');
    Route::get('/exports/history/{batch}/download', [ExportHistoryController::class, 'downloadFile'])->name('exports.history.download');
    Route::get('/settings/user-sessions', UserSessionHistory::class)->name('users.session-history');
    Route::get('/help/guide', UserGuideController::class)->name('help.guide');
    Route::get('/help/roles', RoleGuideController::class)->name('help.roles');
});

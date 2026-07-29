<?php

use App\Http\Controllers\Api\SystemHealthCheckApiController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\ImportExport\ExportHistoryController;
use App\Http\Controllers\ImportExport\ImportHistoryController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RoleGuideController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGuideController;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Companies\CompanyDetail;
use App\Livewire\Companies\CompanyEditor;
use App\Livewire\Companies\CompanyList;
use App\Livewire\Companies\Customer360;
use App\Livewire\Contacts\ContactDetail;
use App\Livewire\Contacts\ContactEditor;
use App\Livewire\Contacts\ContactList;
use App\Livewire\Customers\CustomerMergeTool;
use App\Livewire\Customers\CustomerSlaDashboard;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\ImportExport\ExportHistoryIndex;
use App\Livewire\ImportExport\ImportHistoryIndex;
use App\Livewire\Imports\LeadImportWizard;
use App\Livewire\Leads\LeadRoutingRules;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Notifications\NotificationPreferences;
use App\Livewire\Opportunities\OpportunityDetail;
use App\Livewire\Opportunities\OpportunityEditor;
use App\Livewire\Opportunities\OpportunityKanban;
use App\Livewire\Opportunities\OpportunityList;
use App\Livewire\Pipelines\PipelineDetail;
use App\Livewire\Pipelines\PipelineEditor;
use App\Livewire\Pipelines\PipelineList;
use App\Livewire\Platform\SystemConsole;
use App\Livewire\Quotes\ApprovalInbox;
use App\Livewire\Quotes\QuoteSettings;
use App\Livewire\Reports\DataQualityDashboard;
use App\Livewire\Reports\FunnelReport;
use App\Livewire\Reports\RevenueReport;
use App\Livewire\Reports\SalesPerformanceReport;
use App\Livewire\Roles\PermissionMatrixView;
use App\Livewire\Settings\SessionManager;
use App\Livewire\Staff\StaffList;
use App\Livewire\Tasks\TaskCalendar;
use App\Livewire\Tasks\TaskCreate;
use App\Livewire\Tasks\TaskKanban;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Tasks\TaskShow;
use App\Livewire\Users\UserSessionHistory;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Quote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/register/invitation/{token}', AcceptInvitation::class)->name('invitations.accept');

Route::middleware(['auth', 'request.context.authenticated', 'verified', 'account.active'])->group(function (): void {
    Route::get('/dashboard', DashboardOverview::class)->middleware('can:reports.view')->name('dashboard');
    Route::get('/leads', [LeadController::class, 'index'])->middleware('can:viewAny,'.Lead::class)->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->middleware('can:create,'.Lead::class)->name('leads.create');
    Route::get('/leads/trash', [LeadController::class, 'trash'])->middleware('can:viewTrash,'.Lead::class)->name('leads.trash');
    Route::get('/leads/routing-rules', LeadRoutingRules::class)->middleware('can:leads.assign')->name('leads.routing-rules');
    Route::get('/leads/{leadId}', [LeadController::class, 'show'])->whereNumber('leadId')->name('leads.show');
    Route::get('/leads/{leadId}/edit', [LeadController::class, 'edit'])->whereNumber('leadId')->name('leads.edit');

    Route::get('/notifications', NotificationCenter::class)->name('notifications.index');
    Route::get('/notifications/settings', NotificationPreferences::class)->name('notifications.settings');
    Route::get('/imports/leads', LeadImportWizard::class)->middleware('can:leads.import')->name('imports.leads');
    Route::get('/exports/download/{batch}', ExportDownloadController::class)
        ->name('exports.download')
        ->middleware('signed');

    Route::get('/companies', CompanyList::class)->middleware('can:viewAny,'.Company::class)->name('companies.index');
    Route::get('/companies/create', CompanyEditor::class)->middleware('can:create,'.Company::class)->name('companies.create');
    Route::get('/companies/{companyId}', CompanyDetail::class)->whereNumber('companyId')->name('companies.show');
    Route::get('/companies/{companyId}/edit', CompanyEditor::class)->whereNumber('companyId')->name('companies.edit');

    Route::get('/contacts', ContactList::class)->middleware('can:viewAny,'.Contact::class)->name('contacts.index');
    Route::get('/contacts/create', ContactEditor::class)->middleware('can:create,'.Contact::class)->name('contacts.create');
    Route::get('/contacts/{contactId}', ContactDetail::class)->whereNumber('contactId')->name('contacts.show');
    Route::get('/contacts/{contactId}/edit', ContactEditor::class)->whereNumber('contactId')->name('contacts.edit');
    Route::get('/companies/{companyId}/360', Customer360::class)->whereNumber('companyId')->name('companies.360');
    Route::get('/customers/merge', CustomerMergeTool::class)->middleware('can:companies.update')->name('customers.merge');
    Route::get('/customers/sla', CustomerSlaDashboard::class)->middleware('can:viewAny,'.Company::class)->name('customers.sla');

    Route::get('/opportunities', OpportunityList::class)->middleware('can:viewAny,'.Opportunity::class)->name('opportunities.index');
    Route::get('/opportunities/kanban', OpportunityKanban::class)->middleware('can:viewAny,'.Opportunity::class)->name('opportunities.kanban');
    Route::get('/opportunities/create', OpportunityEditor::class)->middleware('can:create,'.Opportunity::class)->name('opportunities.create');
    Route::get('/opportunities/{opportunityId}', OpportunityDetail::class)->whereNumber('opportunityId')->name('opportunities.show');
    Route::get('/opportunities/{opportunityId}/edit', OpportunityEditor::class)->whereNumber('opportunityId')->name('opportunities.edit');
    Route::get('/quotes/approvals/inbox', ApprovalInbox::class)
        ->middleware('can:quotes.approve')
        ->name('quotes.approvals');
    Route::get('/quotes/{quoteId}', [QuoteController::class, 'show'])->whereNumber('quoteId')->name('quotes.show');
    Route::get('/quotes/{quoteId}/documents/{documentId}', [QuoteController::class, 'download'])
        ->whereNumber(['quoteId', 'documentId'])
        ->middleware('signed')
        ->name('quotes.documents.download');

    Route::get('/pipelines', PipelineList::class)->middleware('can:viewAny,'.Pipeline::class)->name('pipelines.index');
    Route::get('/pipelines/create', PipelineEditor::class)->middleware('can:create,'.Pipeline::class)->name('pipelines.create');
    Route::get('/pipelines/{pipelineId}', PipelineDetail::class)->whereNumber('pipelineId')->name('pipelines.show');
    Route::get('/pipelines/{pipelineId}/edit', PipelineEditor::class)->whereNumber('pipelineId')->name('pipelines.edit');

    Route::get('/tasks', TaskList::class)->middleware('can:viewAny,'.Task::class)->name('tasks.index');
    Route::get('/tasks/create', TaskCreate::class)->middleware('can:create,'.Task::class)->name('tasks.create');
    Route::get('/tasks/kanban', TaskKanban::class)->middleware('can:viewAny,'.Task::class)->name('tasks.kanban');
    Route::get('/tasks/calendar', TaskCalendar::class)->middleware('can:viewAny,'.Task::class)->name('tasks.calendar');
    Route::get('/tasks/{taskId}', TaskShow::class)->whereNumber('taskId')->name('tasks.show');

    Route::get('/reports/funnel', FunnelReport::class)->middleware('can:reports.view')->name('reports.funnel');
    Route::get('/reports/revenue', RevenueReport::class)->middleware('can:reports.view')->name('reports.revenue');
    Route::get('/reports/performance', SalesPerformanceReport::class)->middleware('can:reports.view')->name('reports.performance');
    Route::get('/reports/data-quality', DataQualityDashboard::class)->middleware('can:reports.view')->name('reports.data-quality');

    Route::get('/settings/users', [UserController::class, 'index'])->middleware('can:viewAny,'.User::class)->name('users.index');
    Route::get('/settings/staff', StaffList::class)->middleware('can:viewAny,'.User::class)->name('staff.index');
    Route::get('/settings/departments', [DepartmentController::class, 'index'])
        ->middleware('can:viewAny,'.Department::class)
        ->name('departments.index');
    Route::get('/settings/permission-matrix', PermissionMatrixView::class)->middleware('can:roles.manage')->name('roles.permission-matrix');
    Route::get('/settings/audit-logs', [AuditLogController::class, 'index'])->middleware('can:audit-logs.view')->name('audit-logs.index');
    Route::get('/settings/system-console', SystemConsole::class)->middleware('can:system-console.view')->name('system-console.index');
    Route::get('/settings/system-console/health-api', SystemHealthCheckApiController::class)->middleware('can:system-console.view')->name('system-console.health-api');
    Route::get('/settings/sessions', SessionManager::class)->name('sessions.index');
    Route::get('/settings/quotes', QuoteSettings::class)
        ->middleware('can:manageSettings,'.Quote::class)
        ->name('quotes.settings');
    Route::get('/imports/history', ImportHistoryIndex::class)->name('imports.history');
    Route::get('/imports/history/{batch}/error-log', [ImportHistoryController::class, 'downloadErrorLog'])->name('imports.history.download-errors');
    Route::get('/exports/history', ExportHistoryIndex::class)->name('exports.history');
    Route::get('/exports/history/{batch}/download', [ExportHistoryController::class, 'downloadFile'])->name('exports.history.download');
    Route::get('/settings/user-sessions', UserSessionHistory::class)->name('users.session-history');
    Route::get('/help/guide', UserGuideController::class)->name('help.guide');
    Route::get('/help/roles', RoleGuideController::class)->name('help.roles');
});

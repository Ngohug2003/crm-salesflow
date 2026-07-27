<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Services\CustomerSlaService;
use Illuminate\Console\Command;

final class ScanSlaWarningsCommand extends Command
{
    /** @var string */
    protected $signature = 'crm:scan-sla-warnings';

    /** @var string */
    protected $description = 'Scan CRM records (Leads, Opportunities, Companies) for SLA warnings and breaches';

    public function handle(CustomerSlaService $slaService): int
    {
        $this->info('Quét danh sách hồ sơ kiểm tra hạn SLA chăm sóc...');

        $breachedCount = 0;
        $warningCount = 0;

        // 1. Leads
        $leads = Lead::query()->whereNull('converted_at')->get();
        foreach ($leads as $lead) {
            $sla = $slaService->getSlaInfo($lead);
            if ($sla['status'] === 'breached') {
                $breachedCount++;
                $this->warn("Lead #{$lead->id} ({$lead->full_name}) - Vi phạm SLA ({$sla['hours_since_interaction']}h / {$sla['target_hours']}h)");
            } elseif ($sla['status'] === 'warning') {
                $warningCount++;
                $this->line("Lead #{$lead->id} ({$lead->full_name}) - Sắp quá hạn SLA ({$sla['hours_since_interaction']}h / {$sla['target_hours']}h)");
            }
        }

        // 2. Opportunities
        $opportunities = Opportunity::query()->open()->get();
        foreach ($opportunities as $opp) {
            $sla = $slaService->getSlaInfo($opp);
            if ($sla['status'] === 'breached') {
                $breachedCount++;
                $this->warn("Opportunity #{$opp->id} ({$opp->title}) - Vi phạm SLA ({$sla['hours_since_interaction']}h / {$sla['target_hours']}h)");
            } elseif ($sla['status'] === 'warning') {
                $warningCount++;
                $this->line("Opportunity #{$opp->id} ({$opp->title}) - Sắp quá hạn SLA ({$sla['hours_since_interaction']}h / {$sla['target_hours']}h)");
            }
        }

        // 3. Companies
        $companies = Company::query()->get();
        foreach ($companies as $company) {
            $sla = $slaService->getSlaInfo($company);
            if ($sla['status'] === 'breached') {
                $breachedCount++;
                $this->warn("Company #{$company->id} ({$company->name}) - Vi phạm SLA ({$sla['hours_since_interaction']}h / {$sla['target_hours']}h)");
            } elseif ($sla['status'] === 'warning') {
                $warningCount++;
                $this->line("Company #{$company->id} ({$company->name}) - Sắp quá hạn SLA ({$sla['hours_since_interaction']}h / {$sla['target_hours']}h)");
            }
        }

        $this->info("Đã quét xong: {$breachedCount} vi phạm SLA, {$warningCount} sắp quá hạn.");

        return self::SUCCESS;
    }
}

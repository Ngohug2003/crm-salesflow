<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OpportunityRiskService;
use Illuminate\Console\Command;

final class DetectAtRiskDeals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salesflow:detect-at-risk-deals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate risk scores and detect at-risk opportunities';

    public function handle(OpportunityRiskService $riskService): int
    {
        $this->info('Starting evaluation of at-risk deals...');

        $evaluatedCount = $riskService->evaluateAllActive();

        $this->info("Completed evaluating {$evaluatedCount} active opportunities for risk factors.");

        return self::SUCCESS;
    }
}

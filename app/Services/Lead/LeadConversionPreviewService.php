<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class LeadConversionPreviewService
{
    /**
     * Analyze Lead data and return matching existing Companies/Contacts & pipeline default stages.
     *
     * @return array{
     *     matchedCompanies: Collection<int, Company>,
     *     suggestedCompanyId: ?int,
     *     matchedContacts: Collection<int, Contact>,
     *     suggestedContactId: ?int,
     *     pipelines: Collection<int, Pipeline>,
     *     defaultPipelineId: ?int,
     *     defaultStageId: ?int,
     *     allCompanies: Collection<int, Company>,
     *     allContacts: Collection<int, Contact>
     * }
     */
    public function preview(User $actor, Lead $lead): array
    {
        $companyName = trim((string) $lead->company_name);
        $website = trim((string) $lead->website);

        /** @var Collection<int, Company> $matchedCompanies */
        $matchedCompanies = collect();
        if ($companyName !== '' || $website !== '') {
            $matchedCompanies = Company::query()
                ->where(static function ($query) use ($companyName, $website): void {
                    if ($companyName !== '') {
                        $query->where('name', 'LIKE', "%{$companyName}%");
                    }
                    if ($website !== '') {
                        $query->orWhere('website', 'LIKE', "%{$website}%");
                    }
                })
                ->limit(5)
                ->get();
        }

        $email = trim((string) $lead->email);
        $phone = trim((string) $lead->phone);

        /** @var Collection<int, Contact> $matchedContacts */
        $matchedContacts = collect();
        if ($email !== '' || $phone !== '') {
            $matchedContacts = Contact::query()
                ->where(static function ($query) use ($email, $phone): void {
                    if ($email !== '') {
                        $query->where('email', $email);
                    }
                    if ($phone !== '') {
                        $query->orWhere('phone', $phone);
                    }
                })
                ->limit(5)
                ->get();
        }

        /** @var Collection<int, Pipeline> $pipelines */
        $pipelines = Pipeline::query()->where('is_active', true)->with('stages')->get();
        /** @var Pipeline|null $defaultPipeline */
        $defaultPipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();
        $defaultPipelineId = $defaultPipeline?->id;

        $defaultStageId = null;
        if ($defaultPipeline !== null) {
            /** @var PipelineStage|null $firstStage */
            $firstStage = PipelineStage::query()
                ->where('pipeline_id', $defaultPipeline->id)
                ->orderBy('position', 'asc')
                ->first();
            $defaultStageId = $firstStage?->id;
        }

        /** @var Collection<int, Company> $allCompanies */
        $allCompanies = Company::query()->orderBy('name')->limit(50)->get();

        /** @var Collection<int, Contact> $allContacts */
        $allContacts = Contact::query()->orderBy('full_name')->limit(50)->get();

        return [
            'matchedCompanies' => $matchedCompanies,
            'suggestedCompanyId' => $matchedCompanies->first()?->id,
            'matchedContacts' => $matchedContacts,
            'suggestedContactId' => $matchedContacts->first()?->id,
            'pipelines' => $pipelines,
            'defaultPipelineId' => $defaultPipelineId,
            'defaultStageId' => $defaultStageId,
            'allCompanies' => $allCompanies,
            'allContacts' => $allContacts,
        ];
    }
}

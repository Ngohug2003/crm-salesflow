<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class OpportunityEditor extends Component
{
    public ?int $opportunityId = null;

    public string $title = '';

    public string $code = '';

    public string $amount = '0';

    public ?int $pipeline_id = null;

    public ?int $stage_id = null;

    public ?int $company_id = null;

    public ?int $contact_id = null;

    public ?string $expected_close_date = null;

    public ?int $owner_id = null;

    public string $notes = '';

    public string $forecast_category = 'pipeline';

    public function mount(?int $opportunityId = null): void
    {
        /** @var User $actor */
        $actor = Auth::user();

        if ($opportunityId !== null) {
            $this->opportunityId = $opportunityId;
            /** @var OpportunityManagementService $service */
            $service = app(OpportunityManagementService::class);
            $opportunity = $service->get($actor, $opportunityId);

            Gate::forUser($actor)->authorize('update', $opportunity);

            $this->title = $opportunity->title;
            $this->code = $opportunity->code;
            $this->amount = (string) $opportunity->amount;
            $this->pipeline_id = $opportunity->pipeline_id;
            $this->stage_id = $opportunity->stage_id;
            $this->company_id = $opportunity->company_id;
            $this->contact_id = $opportunity->contact_id;
            $this->expected_close_date = $opportunity->expected_close_date !== null
                ? substr((string) $opportunity->expected_close_date, 0, 10)
                : null;
            $this->owner_id = $opportunity->owner_id;
            $this->notes = (string) $opportunity->notes;
            $this->forecast_category = $opportunity->forecast_category->value;
        } else {
            Gate::forUser($actor)->authorize('create', Opportunity::class);

            $defaultPipeline = Pipeline::query()->where('is_default', true)->with('stages')->first()
                ?? Pipeline::query()->where('is_active', true)->with('stages')->first();

            if ($defaultPipeline !== null) {
                $this->pipeline_id = $defaultPipeline->id;
                $firstStage = $defaultPipeline->stages->first();
                if ($firstStage !== null) {
                    $this->stage_id = $firstStage->id;
                }
            }

            $this->owner_id = $actor->getKey();
        }
    }

    public function updatedPipelineId(int $value): void
    {
        $pipeline = Pipeline::query()->with('stages')->find($value);
        if ($pipeline !== null) {
            $firstStage = $pipeline->stages->first();
            $this->stage_id = $firstStage?->id;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'pipeline_id' => ['required', 'integer', 'exists:pipelines,id'],
            'stage_id' => ['required', 'integer', 'exists:pipeline_stages,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'expected_close_date' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'forecast_category' => ['required', 'string', 'in:omitted,pipeline,best_case,commit,closed'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);

        $payload = [
            'title' => $this->title,
            'code' => $this->code,
            'amount' => (float) $this->amount,
            'pipeline_id' => $this->pipeline_id,
            'stage_id' => $this->stage_id,
            'forecast_category' => $this->forecast_category,
            'company_id' => $this->company_id,
            'contact_id' => $this->contact_id,
            'expected_close_date' => $this->expected_close_date,
            'owner_id' => $this->owner_id,
            'notes' => $this->notes,
        ];

        if ($this->opportunityId !== null) {
            $opportunity = $service->update($actor, $this->opportunityId, $payload);
            session()->flash('message', "Đã cập nhật cơ hội bán hàng '{$opportunity->title}'.");
        } else {
            $opportunity = $service->create($actor, $payload);
            session()->flash('message', "Đã tạo mới cơ hội bán hàng '{$opportunity->title}'.");
        }

        $this->redirect(route('opportunities.show', $opportunity->id), navigate: true);
    }

    /** @return Collection<int, Pipeline> */
    #[Computed]
    public function pipelines(): Collection
    {
        return Pipeline::query()->where('is_active', true)->with('stages')->get();
    }

    /** @return Collection<int, PipelineStage> */
    #[Computed]
    public function stages(): Collection
    {
        if ($this->pipeline_id === null) {
            return collect();
        }

        return PipelineStage::query()->where('pipeline_id', $this->pipeline_id)->orderBy('position', 'asc')->get();
    }

    /** @return Collection<int, Company> */
    #[Computed]
    public function companies(): Collection
    {
        return Company::query()->orderBy('name', 'asc')->get();
    }

    /** @return Collection<int, Contact> */
    #[Computed]
    public function contacts(): Collection
    {
        $query = Contact::query();
        if ($this->company_id !== null) {
            $query->where('company_id', $this->company_id);
        }

        return $query->orderBy('first_name', 'asc')->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name', 'asc')->get();
    }

    public function render(): View
    {
        return view('livewire.opportunities.opportunity-editor');
    }
}

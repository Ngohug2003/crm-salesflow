<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\CustomerTimelineItemData;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\CustomerTimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class CustomerTimelineFeed extends Component
{
    public string $modelType = '';

    public int $modelId = 0;

    public function mount(string $modelType, int $modelId): void
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
    }

    #[On('attachment-updated')]
    public function refreshTimeline(): void {}

    /** @return list<CustomerTimelineItemData> */
    #[Computed]
    public function timeline(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var CustomerTimelineService $service */
        $service = app(CustomerTimelineService::class);

        $targetModel = $this->resolveModel();
        if ($targetModel === null) {
            return [];
        }

        return $service->timelineForModel($actor, $targetModel);
    }

    private function resolveModel(): ?Model
    {
        if ($this->modelType === Company::class || $this->modelType === 'company' || $this->modelType === (new Company)->getMorphClass()) {
            return Company::query()->find($this->modelId);
        }
        if ($this->modelType === Contact::class || $this->modelType === 'contact' || $this->modelType === (new Contact)->getMorphClass()) {
            return Contact::query()->find($this->modelId);
        }
        if ($this->modelType === Opportunity::class || $this->modelType === 'opportunity' || $this->modelType === (new Opportunity)->getMorphClass()) {
            return Opportunity::query()->find($this->modelId);
        }
        if (class_exists($this->modelType) && is_subclass_of($this->modelType, Model::class)) {
            return $this->modelType::query()->find($this->modelId);
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.customers.customer-timeline-feed');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\CustomerTimelineItemData;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ActivityManagementService;
use App\Services\CustomerTimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class CustomerTimelineFeed extends Component
{
    public string $modelType = '';

    public int $modelId = 0;

    public string $filterType = '';

    public bool $showActivityModal = false;

    public ?int $editingActivityId = null;

    public ?int $confirmingDeleteActivityId = null;

    public string $activityType = 'call';

    public string $activityTitle = '';

    public string $activityDescription = '';

    public string $activityPerformedAt = '';

    public ?int $activityDuration = 30;

    public string $activityLocation = '';

    public function mount(string $modelType, int $modelId): void
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->activityPerformedAt = now()->format('Y-m-d\TH:i');
    }

    #[On('attachment-updated')]
    #[On('activity-updated')]
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

        $allTimeline = $service->timelineForModel($actor, $targetModel);

        if ($this->filterType !== '') {
            return array_values(array_filter(
                $allTimeline,
                fn (CustomerTimelineItemData $item) => $item->type === 'activity' && ($item->metadata['activity_type'] ?? '') === $this->filterType
            ));
        }

        return $allTimeline;
    }

    public function openCreateActivity(): void
    {
        $this->resetValidation();
        $this->editingActivityId = null;
        $this->activityType = 'call';
        $this->activityTitle = '';
        $this->activityDescription = '';
        $this->activityPerformedAt = now()->format('Y-m-d\TH:i');
        $this->activityDuration = 30;
        $this->activityLocation = '';
        $this->showActivityModal = true;
    }

    public function editActivity(int $activityId): void
    {
        $this->resetValidation();
        /** @var Activity|null $activity */
        $activity = Activity::query()->find($activityId);
        if ($activity === null) {
            return;
        }

        $type = $activity->activity_type;

        $performedAt = $activity->performed_at instanceof Carbon
            ? $activity->performed_at
            : Carbon::parse((string) $activity->performed_at);

        $this->editingActivityId = $activity->id;
        $this->activityType = $type->value;
        $this->activityTitle = $activity->title;
        $this->activityDescription = $activity->description ?: '';
        $this->activityPerformedAt = $performedAt->format('Y-m-d\TH:i');
        $this->activityDuration = $activity->duration_minutes;
        $this->activityLocation = $activity->location ?: '';
        $this->showActivityModal = true;
    }

    public function saveActivity(): void
    {
        $this->validate([
            'activityTitle' => ['required', 'string', 'max:255'],
            'activityType' => ['required', 'string'],
        ], [
            'activityTitle.required' => 'Vui lòng nhập tiêu đề tương tác.',
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var ActivityManagementService $service */
        $service = app(ActivityManagementService::class);
        $targetModel = $this->resolveModel();

        if ($targetModel === null) {
            return;
        }

        try {
            $data = [
                'activity_type' => ActivityType::from($this->activityType),
                'title' => $this->activityTitle,
                'description' => $this->activityDescription !== '' ? $this->activityDescription : null,
                'performed_at' => $this->activityPerformedAt !== '' ? $this->activityPerformedAt : now(),
                'duration_minutes' => $this->activityDuration,
                'location' => $this->activityLocation !== '' ? $this->activityLocation : null,
            ];

            if ($this->editingActivityId !== null) {
                $service->updateActivity($actor, $this->editingActivityId, $data);
                session()->flash('activity_message', 'Cập nhật tương tác thành công.');
            } else {
                $service->createActivity($actor, $targetModel, $data);
                session()->flash('activity_message', 'Tạo mới tương tác thành công.');
            }

            $this->showActivityModal = false;
            $this->dispatch('activity-updated');
        } catch (\Throwable $e) {
            $this->addError('activity_error', $e->getMessage());
        }
    }

    public function confirmDeleteActivity(int $activityId): void
    {
        $this->confirmingDeleteActivityId = $activityId;
    }

    public function deleteConfirmedActivity(): void
    {
        if ($this->confirmingDeleteActivityId !== null) {
            $this->deleteActivity($this->confirmingDeleteActivityId);
            $this->confirmingDeleteActivityId = null;
        }
    }

    public function deleteActivity(int $activityId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var ActivityManagementService $service */
        $service = app(ActivityManagementService::class);

        try {
            $service->deleteActivity($actor, $activityId);
            session()->flash('activity_message', 'Đã xóa tương tác thành công.');
            $this->dispatch('activity-updated');
        } catch (\Throwable $e) {
            $this->addError('activity_error', $e->getMessage());
        }
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
        if ($this->modelType === Lead::class || $this->modelType === 'lead' || $this->modelType === (new Lead)->getMorphClass()) {
            return Lead::query()->find($this->modelId);
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

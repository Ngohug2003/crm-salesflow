<?php

declare(strict_types=1);

namespace App\Livewire\Tasks;

use App\Data\TaskFilterData;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepository;
use App\Services\TaskManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class TaskCalendar extends Component
{
    #[Url(as: 'month', history: true)]
    public string $selectedMonth = '';

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Task::class);

        if ($this->selectedMonth === '') {
            $this->selectedMonth = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth) ?? now();
        $this->selectedMonth = $date->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth) ?? now();
        $this->selectedMonth = $date->addMonth()->format('Y-m');
    }

    public function currentMonth(): void
    {
        $this->selectedMonth = now()->format('Y-m');
    }

    /** @return array<string, array<int, array{id: int, title: string, type: string, time: string, color: string}>> */
    #[Computed]
    public function eventsByDate(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var TaskManagementService $service */
        $service = app(TaskManagementService::class);

        $currentDate = Carbon::createFromFormat('Y-m', $this->selectedMonth) ?? now();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $filters = new TaskFilterData(
            sortBy: 'due_date',
            sortDirection: 'asc',
        );

        $tasks = $service->list($actor, $filters, 200);

        /** @var array<string, array<int, array{id: int, title: string, type: string, time: string, color: string}>> $events */
        $events = [];

        foreach ($tasks->items() as $task) {
            if ($task->due_date) {
                $dateKey = $task->due_date->format('Y-m-d');
                $events[$dateKey][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => 'task',
                    'time' => $task->due_date->format('H:i'),
                    'color' => $task->priority->color(),
                ];
            }
        }

        // Fetch Activities in the month
        $activities = app(ActivityRepository::class)->getVisibleBetween(
            $actor,
            $startOfMonth->startOfDay(),
            $endOfMonth->endOfDay(),
        );

        foreach ($activities as $act) {
            if ($act->performed_at) {
                $dateKey = $act->performed_at->format('Y-m-d');
                $events[$dateKey][] = [
                    'id' => $act->id,
                    'title' => $act->title,
                    'type' => 'activity',
                    'time' => $act->performed_at->format('H:i'),
                    'color' => 'indigo',
                ];
            }
        }

        return $events;
    }

    public function render(): View
    {
        $currentDate = Carbon::createFromFormat('Y-m', $this->selectedMonth) ?? now();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $daysInMonth = $currentDate->daysInMonth;
        $startOfWeekDay = $startOfMonth->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        return view('livewire.tasks.task-calendar', [
            'monthTitle' => $currentDate->format('m/Y'),
            'daysInMonth' => $daysInMonth,
            'startOfWeekDay' => $startOfWeekDay,
            'year' => $currentDate->year,
            'month' => $currentDate->month,
        ]);
    }
}

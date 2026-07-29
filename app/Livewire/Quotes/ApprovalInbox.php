<?php

declare(strict_types=1);

namespace App\Livewire\Quotes;

use App\Enums\QuoteApprovalStatus;
use App\Exceptions\QuoteWorkflowException;
use App\Models\User;
use App\Repositories\Contracts\QuoteRepository;
use App\Services\QuoteApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class ApprovalInbox extends Component
{
    use WithPagination;

    public string $status = 'pending';

    public ?int $selectedRequestId = null;

    public string $decision = 'approve';

    public string $reason = '';

    public bool $showDecisionModal = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless($this->user()->can('quotes.approve'), 403);
    }

    public function openDecision(int $requestId, string $decision): void
    {
        $this->selectedRequestId = $requestId;
        $this->decision = $decision === 'reject' ? 'reject' : 'approve';
        $this->reason = '';
        $this->errorMessage = null;
        $this->showDecisionModal = true;
    }

    public function resolve(QuoteApprovalService $service): void
    {
        $this->validate([
            'selectedRequestId' => ['required', 'integer'],
            'decision' => ['required', 'in:approve,reject'],
            'reason' => [$this->decision === 'reject' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], ['reason.required' => 'Vui lòng nhập lý do từ chối.']);

        try {
            if ($this->decision === 'approve') {
                $service->approve($this->user(), (int) $this->selectedRequestId, $this->reason ?: null);
                session()->flash('success', 'Báo giá đã được phê duyệt.');
            } else {
                $service->reject($this->user(), (int) $this->selectedRequestId, $this->reason);
                session()->flash('success', 'Đã từ chối báo giá và gửi lý do cho người lập.');
            }
        } catch (QuoteWorkflowException $exception) {
            $this->errorMessage = $exception->getMessage();

            return;
        }

        $this->showDecisionModal = false;
        $this->reset('selectedRequestId', 'reason');
    }

    public function render(QuoteRepository $quotes): View
    {
        $allowed = array_column(QuoteApprovalStatus::cases(), 'value');
        $status = in_array($this->status, $allowed, true) ? $this->status : 'pending';

        return view('livewire.quotes.approval-inbox', [
            'requests' => $quotes->approvalInbox($this->user(), $status),
        ]);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

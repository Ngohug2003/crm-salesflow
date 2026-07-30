<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteApprovalStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\QuoteWorkflowException;
use App\Models\QuoteApprovalAction;
use App\Models\QuoteApprovalRequest;
use App\Models\User;
use App\Notifications\QuoteWorkflowNotification;
use App\Repositories\Contracts\QuoteRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

final readonly class QuoteApprovalService
{
    public function __construct(
        private QuoteRepository $quotes,
        private SystemAuditService $audit,
    ) {}

    public function submit(User $actor, int $quoteId, ?string $note = null): QuoteApprovalRequest
    {
        return DB::transaction(function () use ($actor, $quoteId, $note): QuoteApprovalRequest {
            $quote = $this->quotes->findForUpdate($quoteId);
            Gate::forUser($actor)->authorize('submit', $quote);

            if ($quote->items->isEmpty()) {
                throw new QuoteWorkflowException('Báo giá phải có ít nhất một dòng sản phẩm.');
            }

            $previousStatus = $quote->status;
            if ($previousStatus === QuoteStatus::Rejected) {
                $quote->update([
                    'version' => $quote->version + 1,
                    'approved_at' => null,
                    'rejection_reason' => null,
                    'updated_by' => $actor->id,
                ]);
                $quote->refresh();
            }

            $rule = $this->quotes->matchingApprovalRule($quote);
            if ($rule === null) {
                throw new QuoteWorkflowException('Chưa có quy tắc phê duyệt phù hợp. Vui lòng liên hệ quản trị viên.');
            }

            $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
            $isAuthorizedApprover = Gate::forUser($actor)->allows('approve', $quote)
                || $actor->hasRole($superAdminRole)
                || $actor->hasRole('admin');

            $autoApproved = $rule->auto_approve || $isAuthorizedApprover;
            $status = $autoApproved ? QuoteApprovalStatus::Approved : QuoteApprovalStatus::Pending;
            $now = now();

            $request = QuoteApprovalRequest::query()->create([
                'quote_id' => $quote->id,
                'rule_id' => $rule->id,
                'requested_by' => $actor->id,
                'resolved_by' => $autoApproved ? $actor->id : null,
                'quote_version' => $quote->version,
                'status' => $status,
                'required_role' => $rule->required_role,
                'discount_percent' => $quote->discount_percent,
                'total_amount' => $quote->total_amount,
                'request_note' => $note,
                'decision_reason' => $autoApproved
                    ? ($isAuthorizedApprover ? 'Tự động duyệt cho cấp có thẩm quyền phê duyệt.' : 'Tự động duyệt theo hạn mức được cấu hình.')
                    : null,
                'submitted_at' => $now,
                'resolved_at' => $autoApproved ? $now : null,
            ]);

            QuoteApprovalAction::query()->create([
                'approval_request_id' => $request->id,
                'actor_id' => $actor->id,
                'action' => $autoApproved ? 'auto_approved' : 'submitted',
                'reason' => $note,
                'metadata' => ['rule_id' => $rule->id, 'quote_version' => $quote->version],
            ]);

            $quote->update([
                'status' => $autoApproved ? QuoteStatus::Approved : QuoteStatus::PendingApproval,
                'submitted_at' => $now,
                'approved_at' => $autoApproved ? $now : null,
                'rejection_reason' => null,
                'updated_by' => $actor->id,
            ]);

            $this->audit->record(
                $actor,
                $quote,
                $autoApproved ? 'auto-approved' : 'submitted',
                $autoApproved
                    ? "Báo giá {$quote->quote_number} được tự động phê duyệt"
                    : "Gửi báo giá {$quote->quote_number} chờ phê duyệt",
                ['status' => $previousStatus->value],
                ['status' => $quote->status->value, 'approval_request_id' => $request->id],
                ['module' => 'quotes', 'quote_version' => $quote->version],
            );

            if (! $autoApproved && $rule->required_role !== null) {
                DB::afterCommit(function () use ($quote, $rule): void {
                    $roles = $rule->required_role === 'admin'
                        ? ['admin', 'super-admin']
                        : [$rule->required_role];
                    $approvers = User::role($roles)
                        ->where('is_active', true)
                        ->when(
                            $rule->required_role === 'sales-manager',
                            fn ($query) => $query->where('department_id', $quote->opportunity->department_id),
                        )
                        ->get();

                    Notification::send($approvers, new QuoteWorkflowNotification(
                        $quote,
                        'Báo giá cần phê duyệt',
                        "{$quote->quote_number} đang chờ phê duyệt chiết khấu {$quote->discount_percent}%.",
                        'quote_approval_requested',
                    ));
                });
            }

            return $request->fresh(['quote', 'rule', 'requester']);
        });
    }

    public function approve(User $actor, int $requestId, ?string $reason = null): QuoteApprovalRequest
    {
        return $this->resolve($actor, $requestId, QuoteApprovalStatus::Approved, $reason);
    }

    public function reject(User $actor, int $requestId, string $reason): QuoteApprovalRequest
    {
        if (trim($reason) === '') {
            throw new QuoteWorkflowException('Vui lòng nhập lý do từ chối.');
        }

        return $this->resolve($actor, $requestId, QuoteApprovalStatus::Rejected, trim($reason));
    }

    private function resolve(
        User $actor,
        int $requestId,
        QuoteApprovalStatus $decision,
        ?string $reason,
    ): QuoteApprovalRequest {
        return DB::transaction(function () use ($actor, $requestId, $decision, $reason): QuoteApprovalRequest {
            $request = QuoteApprovalRequest::query()
                ->with(['quote.opportunity', 'requester'])
                ->lockForUpdate()
                ->findOrFail($requestId);
            $quote = $request->quote;

            Gate::forUser($actor)->authorize('view', $quote);

            if ($request->requested_by === $actor->id) {
                throw new QuoteWorkflowException('Người lập yêu cầu không được tự phê duyệt báo giá.');
            }

            Gate::forUser($actor)->authorize('approve', $quote);

            if ($request->status !== QuoteApprovalStatus::Pending
                || $quote->status !== QuoteStatus::PendingApproval
                || $request->quote_version !== $quote->version) {
                throw new QuoteWorkflowException('Yêu cầu phê duyệt không còn hiệu lực.');
            }

            if ($request->required_role !== null && ! $actor->hasAnyRole([$request->required_role, 'admin', 'super-admin'])) {
                throw new QuoteWorkflowException('Vai trò hiện tại không đủ thẩm quyền phê duyệt mức chiết khấu này.');
            }

            $now = now();
            $request->update([
                'status' => $decision,
                'resolved_by' => $actor->id,
                'decision_reason' => $reason,
                'resolved_at' => $now,
            ]);
            $quote->update([
                'status' => $decision === QuoteApprovalStatus::Approved ? QuoteStatus::Approved : QuoteStatus::Rejected,
                'approved_at' => $decision === QuoteApprovalStatus::Approved ? $now : null,
                'rejection_reason' => $decision === QuoteApprovalStatus::Rejected ? $reason : null,
                'updated_by' => $actor->id,
            ]);

            QuoteApprovalAction::query()->create([
                'approval_request_id' => $request->id,
                'actor_id' => $actor->id,
                'action' => $decision->value,
                'reason' => $reason,
                'metadata' => ['quote_version' => $quote->version],
            ]);

            $this->audit->record(
                $actor,
                $quote,
                $decision->value,
                ($decision === QuoteApprovalStatus::Approved ? 'Phê duyệt ' : 'Từ chối ')."báo giá {$quote->quote_number}",
                ['status' => QuoteStatus::PendingApproval->value],
                ['status' => $quote->status->value, 'reason' => $reason],
                ['module' => 'quotes', 'approval_request_id' => $request->id],
            );

            DB::afterCommit(fn () => $request->requester->notify(new QuoteWorkflowNotification(
                $quote,
                $decision === QuoteApprovalStatus::Approved ? 'Báo giá đã được duyệt' : 'Báo giá bị từ chối',
                $decision === QuoteApprovalStatus::Approved
                    ? "{$quote->quote_number} đã được phê duyệt và có thể phát hành."
                    : "{$quote->quote_number} bị từ chối: {$reason}",
                $decision === QuoteApprovalStatus::Approved ? 'quote_approved' : 'quote_rejected',
            )));

            return $request->refresh();
        });
    }
}
